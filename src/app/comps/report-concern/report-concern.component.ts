import { Component, NgZone, AfterViewInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { ConcernService } from '../../services/concern.service';
import {
  Concern,
  ConcernCategory,
  ConcernSeverity,
  Location,
  ContactInfo
} from '../../models/concern.model';

declare var L: any;

@Component({
  selector: 'app-report-concern',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './report-concern.component.html',
  styleUrls: ['./report-concern.component.css']
})
export class ReportConcernComponent implements AfterViewInit, OnDestroy {
  /** Default map / pin center (WGS84): 16°24′56″N 120°35′51″E — Leaflet [lat, lng]. */
  private readonly MAP_CENTER_LAT = 16 + 24 / 60 + 56 / 3600;
  private readonly MAP_CENTER_LNG = 120 + 35 / 60 + 51 / 3600;

  /**
   * Campus reporting boundary (rectangle around 16°24′56″N 120°35′51″E).
   * Linear spans are 3/4 of the prior box (reduced by 1/4); used for outline, pan limits, and validation.
   */
  private readonly CAMPUS_SW: [number, number] = [16.414543, 120.595925];
  private readonly CAMPUS_NE: [number, number] = [16.416568, 120.599075];

  currentUser: any = null;
  isSubmitting = false;
  errorMessage = '';
  successMessage = '';

  // Form data
  concernData = {
    title: '',
    description: '',
    category: ConcernCategory.OTHER,
    severity: ConcernSeverity.MEDIUM,
    location: {
      latitude: this.MAP_CENTER_LAT,
      longitude: this.MAP_CENTER_LNG,
      address: '',
      building: '',
      floor: '',
      room: '',
      landmark: ''
    } as Location,
    contactInfo: {
      name: '',
      email: '',
      phone: '',
      studentId: '',
      department: ''
    } as ContactInfo,
    witnesses: [] as string[],
    additionalNotes: '',
    isAnonymous: false
  };

  // Options
  categories = Object.values(ConcernCategory);
  severities = Object.values(ConcernSeverity);
  witnessInput = '';

  private map: any = null;
  private marker: any = null;
  private mapInitialized = false;

  /** Text-only presets: they do not move the map pin; the user sets coordinates on the map. */
  private readonly quickLocationAddresses: Record<string, string> = {
    'Main Library': 'University of Baguio Main Library',
    Gymnasium: 'University Gymnasium',
    'Parking Lot': 'Faculty Parking Lot',
    Administration: 'Administration Building',
    Cafeteria: 'University Cafeteria'
  };

  constructor(
    private authService: AuthService,
    private concernService: ConcernService,
    private router: Router,
    private ngZone: NgZone
  ) {
    this.authService.getCurrentUser().subscribe(user => {
      this.currentUser = user;
      if (user) {
        this.concernData.contactInfo.name = `${user.profile.firstName} ${user.profile.lastName}`;
        this.concernData.contactInfo.email = user.email;
        this.concernData.contactInfo.phone = user.profile.phone || '';
        this.concernData.contactInfo.studentId = user.profile.studentId || '';
        this.concernData.contactInfo.department = user.profile.department || '';
      }
    });
  }

  ngAfterViewInit(): void {
    setTimeout(() => this.initReportMap(), 0);
  }

  ngOnDestroy(): void {
    this.destroyReportMap();
  }

  private initReportMap(): void {
    if (this.mapInitialized) {
      return;
    }
    if (typeof L === 'undefined') {
      console.error('Leaflet not loaded');
      return;
    }
    const mapEl = document.getElementById('report-map');
    if (!mapEl) {
      return;
    }

    const lat = this.concernData.location.latitude;
    const lng = this.concernData.location.longitude;

    this.map = L.map(mapEl, {
      center: [lat, lng],
      zoom: 17,
      maxBounds: [this.CAMPUS_SW, this.CAMPUS_NE],
      maxBoundsViscosity: 0.85
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
      maxZoom: 19
    }).addTo(this.map);

    const sw = this.CAMPUS_SW;
    const ne = this.CAMPUS_NE;
    L.polygon(
      [
        [sw[0], sw[1]],
        [sw[0], ne[1]],
        [ne[0], ne[1]],
        [ne[0], sw[1]],
        [sw[0], sw[1]]
      ],
      {
        color: '#dc3545',
        weight: 2,
        opacity: 0.95,
        fillColor: '#dc3545',
        fillOpacity: 0.06
      }
    ).addTo(this.map);

    this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);

    this.map.on('click', (e: any) => {
      const ll = e.latlng;
      this.marker.setLatLng(ll);
      this.ngZone.run(() => {
        this.concernData.location.latitude = ll.lat;
        this.concernData.location.longitude = ll.lng;
      });
    });

    this.marker.on('dragend', () => {
      const ll = this.marker.getLatLng();
      this.ngZone.run(() => {
        this.concernData.location.latitude = ll.lat;
        this.concernData.location.longitude = ll.lng;
      });
    });

    this.mapInitialized = true;
    const resize = () => this.map?.invalidateSize();
    this.map.whenReady(resize);
    setTimeout(resize, 200);
  }

  private destroyReportMap(): void {
    if (this.map) {
      this.map.remove();
      this.map = null;
      this.marker = null;
      this.mapInitialized = false;
    }
  }

  onSubmit() {
    if (!this.validateForm()) {
      return;
    }

    this.isSubmitting = true;
    this.errorMessage = '';
    this.successMessage = '';

    const concern: Omit<Concern, 'id' | 'reportedAt' | 'updatedAt'> = {
      title: this.concernData.title,
      description: this.concernData.description,
      category: this.concernData.category,
      severity: this.concernData.severity,
      status: 'reported' as any,
      location: this.concernData.location,
      reportedBy: this.currentUser?.id || 'anonymous',
      images: [],
      witnesses: this.concernData.witnesses,
      contactInfo: this.concernData.isAnonymous ? {
        name: 'Anonymous',
        email: 'anonymous@ub.edu.ph'
      } : this.concernData.contactInfo,
      additionalNotes: this.concernData.additionalNotes,
      isAnonymous: this.concernData.isAnonymous
    };

    this.concernService.createConcern(concern).subscribe(
      (createdConcern) => {
        this.isSubmitting = false;
        this.successMessage = 'Concern reported successfully! Redirecting to dashboard...';
        setTimeout(() => {
          this.router.navigate(['/dashboard']);
        }, 2000);
      },
      (error) => {
        this.isSubmitting = false;
        this.errorMessage = 'Failed to report concern. Please try again.';
      }
    );
  }

  private validateForm(): boolean {
    this.errorMessage = '';
    this.successMessage = '';

    if (!this.concernData.title.trim()) {
      this.errorMessage = 'Please provide the concern title';
      return false;
    }

    if (!this.concernData.description.trim()) {
      this.errorMessage = 'Please provide the concern description';
      return false;
    }

    if (!this.concernData.location.address.trim()) {
      this.errorMessage = 'Please provide the concern location';
      return false;
    }

    // Validate that location is within University of Baguio campus boundaries
    if (!this.isWithinCampusBounds(this.concernData.location.latitude, this.concernData.location.longitude)) {
      this.errorMessage = 'Concerns can only be reported within University of Baguio campus boundaries. Please select a location within the campus area.';
      return false;
    }

    if (!this.concernData.isAnonymous && (!this.concernData.contactInfo.name || !this.concernData.contactInfo.email)) {
      this.errorMessage = 'Please provide contact information';
      return false;
    }

    return true;
  }

  private isWithinCampusBounds(latitude: number, longitude: number): boolean {
    return (
      latitude >= this.CAMPUS_SW[0] &&
      latitude <= this.CAMPUS_NE[0] &&
      longitude >= this.CAMPUS_SW[1] &&
      longitude <= this.CAMPUS_NE[1]
    );
  }

  addWitness() {
    if (this.witnessInput.trim()) {
      this.concernData.witnesses.push(this.witnessInput.trim());
      this.witnessInput = '';
    }
  }

  removeWitness(index: number) {
    this.concernData.witnesses.splice(index, 1);
  }

  setQuickLocation(key: string) {
    const address = this.quickLocationAddresses[key];
    if (address) {
      this.concernData.location.address = address;
    }
  }

  cancel() {
    this.router.navigate(['/dashboard']);
  }
}
