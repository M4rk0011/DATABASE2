import { Component, OnInit, AfterViewInit, OnDestroy, NgZone } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { IncidentService } from '../../services/incident.service';
import { AuthService } from '../../services/auth.service';
import { MapMarker, Incident, IncidentStatus } from '../../models/incident.model';

declare var L: any;

@Component({
  selector: 'app-incident-map',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './incident-map.component.html',
  styleUrls: ['./incident-map.component.css']
})
export class IncidentMapComponent implements OnInit, AfterViewInit, OnDestroy {
  markers: MapMarker[] = [];
  selectedIncident: Incident | null = null;
  map: any;
  private incidentLayerGroup: any = null;
  isUserAuthenticated = false;
  isAdmin = false;
  isSecurity = false;
  mapInitialized = false;
  statusPickerOpen = false;
  assignPickerOpen = false;

  readonly incidentStatusOptions: IncidentStatus[] = [
    IncidentStatus.REPORTED,
    IncidentStatus.INVESTIGATING,
    IncidentStatus.RESOLVED,
    IncidentStatus.CLOSED,
    IncidentStatus.FALSE_ALARM
  ];

  /**
   * Map center (WGS84): 16°24′56″N 120°35′51″E
   * (≈ 16.415556°N, 120.597500°E). Leaflet expects [latitude, longitude].
   */
  private readonly MAP_CENTER_LAT = 16 + 24 / 60 + 56 / 3600;
  private readonly MAP_CENTER_LNG = 120 + 35 / 60 + 51 / 3600;
  private readonly DEFAULT_ZOOM = 18;

  /** Same campus reporting rectangle as report form + API validation */
  private readonly CAMPUS_SW: [number, number] = [16.414543, 120.595925];
  private readonly CAMPUS_NE: [number, number] = [16.416568, 120.599075];

  private getMapCenter(): [number, number] {
    return [this.MAP_CENTER_LAT, this.MAP_CENTER_LNG];
  }

  constructor(
    private incidentService: IncidentService,
    private authService: AuthService,
    private ngZone: NgZone,
    private router: Router
  ) {}

  ngOnInit() {
    this.loadMarkers();
    this.checkAuthStatus();
  }

  ngAfterViewInit() {
    setTimeout(() => {
      this.initializeMap();
    }, 0);
  }

  ngOnDestroy(): void {
    if (this.map) {
      this.map.remove();
      this.map = null;
      this.incidentLayerGroup = null;
      this.mapInitialized = false;
    }
  }

  private checkAuthStatus() {
    this.authService.isAuthenticated().subscribe(isAuth => {
      this.isUserAuthenticated = isAuth;
      this.isAdmin = this.authService.isAdmin();
      this.isSecurity = this.authService.isSecurity();
    });
  }

  canManageIncidents(): boolean {
    return this.isAdmin || this.isSecurity;
  }

  editSelectedIncident(): void {
    if (!this.selectedIncident) {
      return;
    }
    this.router.navigate(['/incidents', this.selectedIncident.id]);
  }

  toggleStatusPicker(): void {
    this.assignPickerOpen = false;
    this.statusPickerOpen = !this.statusPickerOpen;
  }

  toggleAssignPicker(): void {
    this.statusPickerOpen = false;
    this.assignPickerOpen = !this.assignPickerOpen;
  }

  formatStatusLabel(status: IncidentStatus): string {
    return status.replace(/_/g, ' ');
  }

  applyStatus(status: IncidentStatus): void {
    if (!this.selectedIncident) {
      return;
    }
    const updates: Partial<Incident> = { status };
    if (status === IncidentStatus.RESOLVED) {
      updates.resolvedAt = new Date();
    }
    this.incidentService.updateIncident(this.selectedIncident.id, updates).subscribe({
      next: updated => {
        this.ngZone.run(() => {
          this.selectedIncident = updated;
          this.loadMarkers();
          this.statusPickerOpen = false;
        });
      },
      error: err => {
        console.error(err);
        alert(err?.message ?? 'Could not update status.');
      }
    });
  }

  assignToStaff(assignee: string): void {
    if (!this.selectedIncident) {
      return;
    }
    this.incidentService
      .updateIncident(this.selectedIncident.id, { assignedTo: assignee })
      .subscribe({
        next: updated => {
          this.ngZone.run(() => {
            this.selectedIncident = updated;
            this.loadMarkers();
            this.assignPickerOpen = false;
          });
        },
        error: err => {
          console.error(err);
          alert(err?.message ?? 'Could not assign incident.');
        }
      });
  }

  private initializeMap() {
    if (this.mapInitialized) {
      return;
    }

    // Check if Leaflet is available
    if (typeof L === 'undefined') {
      console.error('Leaflet not loaded');
      this.loadMapFallback();
      return;
    }

    // Map view stays centered on official University of Baguio (16°24′56″N 120°35′51″E); do not fitBounds on load.
    const mapElement = document.getElementById('map');
    if (mapElement) {
      const center = this.getMapCenter();
      this.map = L.map('map', {
        center,
        zoom: this.DEFAULT_ZOOM,
        zoomControl: true,
        minZoom: 16,
        maxZoom: 20,
        maxBounds: [this.CAMPUS_SW, this.CAMPUS_NE],
        maxBoundsViscosity: 0.85
      });
      
      // Add OpenStreetMap tile layer
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 20
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

      this.incidentLayerGroup = L.layerGroup().addTo(this.map);
      
      this.mapInitialized = true;

      // Recenter after layout (grid/flex often reports wrong size on first paint)
      const applyCenter = () => {
        if (!this.map) {
          return;
        }
        this.map.invalidateSize();
        this.map.setView(center, this.DEFAULT_ZOOM, { animate: false });
      };
      this.map.whenReady(applyCenter);
      setTimeout(applyCenter, 0);

      // Reference buildings (below incident pins — pins use z-index offset)
      this.addCampusBuildings();

      // Add severity legend as a map-bound control
      this.addSeverityLegend();

      // Lock viewport to official coordinates after layers (layout/size can shift first paint)
      const centerRef = this.getMapCenter();
      const snapToOfficialCenter = () => {
        if (!this.map) {
          return;
        }
        this.map.invalidateSize();
        this.map.setView(centerRef, this.DEFAULT_ZOOM, { animate: false });
      };
      queueMicrotask(snapToOfficialCenter);
      setTimeout(snapToOfficialCenter, 100);

      this.refreshIncidentMarkersOnMap(this.markers);
    }
  }

  private refreshIncidentMarkersOnMap(markerData: MapMarker[]): void {
    if (!this.map || !this.incidentLayerGroup || typeof L === 'undefined') {
      return;
    }
    this.incidentLayerGroup.clearLayers();
    for (const m of markerData) {
      const color = this.getSeverityColor(m.incident.severity);
      const leafletMarker = L.marker([m.position.lat, m.position.lng], {
        zIndexOffset: 750,
        icon: L.divIcon({
          className: 'incident-map-pin-icon',
          html: this.incidentPinIconHtml(color),
          iconSize: [28, 36],
          iconAnchor: [14, 34]
        })
      });
      leafletMarker.bindPopup(
        `<strong>${this.escapeHtml(m.title)}</strong><br>${this.escapeHtml(m.incident.location.address)}`
      );
      leafletMarker.on('click', () => {
        this.ngZone.run(() => this.selectIncident(m.incident));
      });
      leafletMarker.addTo(this.incidentLayerGroup);
    }
  }

  private incidentPinIconHtml(fillColor: string): string {
    const fill = fillColor.replace(/[<>"']/g, '');
    return `
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="36" viewBox="0 0 28 36" aria-hidden="true">
  <path fill="${fill}" stroke="#ffffff" stroke-width="2"
    d="M14 2C8.48 2 4 6.24 4 11.2c0 7.2 10 20.8 10 20.8s10-13.6 10-20.8C24 6.24 19.52 2 14 2z"/>
  <circle cx="14" cy="11" r="3.5" fill="#ffffff"/>
</svg>`.trim();
  }

  private escapeHtml(text: string): string {
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  private addCampusBuildings() {
    // Approximate positions near campus center (aligned with current incident coordinates)
    const buildings = [
      {
        name: 'Main Building',
        position: [16.4156, 120.597],
        icon: 'blue'
      },
      {
        name: 'University Library',
        position: [16.4158, 120.5965],
        icon: 'green'
      },
      {
        name: 'Gymnasium',
        position: [16.4153, 120.5988],
        icon: 'orange'
      },
      {
        name: 'Administration Building',
        position: [16.4159, 120.5961],
        icon: 'purple'
      },
      {
        name: 'Science Building',
        position: [16.4152, 120.5978],
        icon: 'red'
      }
    ];

    buildings.forEach(building => {
      const marker = L.marker(building.position, {
        icon: L.divIcon({
          className: 'custom-div-icon',
          html: `<div style="background-color: ${building.icon}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>`,
          iconSize: [16, 16],
          iconAnchor: [8, 8]
        })
      }).addTo(this.map);

      marker.bindPopup(`<div style="padding: 10px;"><h4>${building.name}</h4><p>University of Baguio</p></div>`);
    });
  }

  private addSeverityLegend() {
    const legendControl = L.control({ position: 'topleft' });
    legendControl.onAdd = () => {
      const div = L.DomUtil.create('div', 'map-severity-legend');
      div.innerHTML = `
        <div style="
          background: white;
          padding: 12px;
          border-radius: 8px;
          box-shadow: 0 2px 10px rgba(0,0,0,0.2);
          min-width: 120px;
          max-width: 150px;
        ">
          <h4 style="margin: 0 0 8px 0; font-size: 0.85em; color: #333; white-space: nowrap;">Severity Legend</h4>
          <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px; font-size: 0.8em;">
            <span style="width: 12px; height: 12px; border-radius: 50%; background: #dc3545; display: inline-block;"></span>
            <span>Critical</span>
          </div>
          <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px; font-size: 0.8em;">
            <span style="width: 12px; height: 12px; border-radius: 50%; background: #fd7e14; display: inline-block;"></span>
            <span>High</span>
          </div>
          <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px; font-size: 0.8em;">
            <span style="width: 12px; height: 12px; border-radius: 50%; background: #ffc107; display: inline-block;"></span>
            <span>Medium</span>
          </div>
          <div style="display: flex; align-items: center; gap: 6px; font-size: 0.8em;">
            <span style="width: 12px; height: 12px; border-radius: 50%; background: #28a745; display: inline-block;"></span>
            <span>Low</span>
          </div>
        </div>
      `;
      return div;
    };
    legendControl.addTo(this.map);
  }

  private loadMarkers() {
    this.incidentService.getMapMarkers().subscribe(markers => {
      this.ngZone.run(() => {
        this.markers = markers;
      });
      this.refreshIncidentMarkersOnMap(markers);
    });
  }

  selectIncident(incident: Incident) {
    this.ngZone.run(() => {
      this.selectedIncident = incident;
    });
    if (
      this.map &&
      incident.location?.latitude != null &&
      incident.location?.longitude != null
    ) {
      const lat = incident.location.latitude;
      const lng = incident.location.longitude;
      this.map.flyTo([lat, lng], Math.max(this.map.getZoom(), this.DEFAULT_ZOOM), {
        duration: 0.45
      });
    }
  }

  closeIncidentDetails() {
    this.selectedIncident = null;
    this.statusPickerOpen = false;
    this.assignPickerOpen = false;
  }

  getSeverityColor(severity: string): string {
    const colors: Record<string, string> = {
      'critical': '#dc3545',
      'high': '#fd7e14',
      'medium': '#ffc107',
      'low': '#28a745'
    };
    return colors[severity] || '#6c757d';
  }

  getStatusColor(status: string): string {
    const colors: Record<string, string> = {
      'reported': '#007bff',
      'investigating': '#fd7e14',
      'resolved': '#28a745',
      'closed': '#6c757d',
      'false_alarm': '#6f42c1'
    };
    return colors[status] || '#6c757d';
  }

  getCriticalCount(): number {
    return this.markers.filter(m => m.incident.severity === 'critical').length;
  }

  getNewReportsCount(): number {
    return this.markers.filter(m => m.incident.status === 'reported').length;
  }

  getResolvedCount(): number {
    return this.markers.filter(m => m.incident.status === 'resolved').length;
  }

  getSeverityInitial(severity: string): string {
    return severity.charAt(0).toUpperCase();
  }

  resetMap() {
    if (this.map) {
      this.map.invalidateSize();
      this.map.setView(this.getMapCenter(), this.DEFAULT_ZOOM, { animate: false });
    }
  }

  toggleMapType() {
    if (this.map) {
      // Toggle between OpenStreetMap and different tile layers
      const currentTileLayer = this.map._layers && Object.values(this.map._layers)[0];
      if (currentTileLayer) {
        this.map.removeLayer(currentTileLayer);
        
        // Add a different tile layer (you can add more options)
        L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
          attribution: '© OpenStreetMap contributors, Tiles style by Humanitarian OpenStreetMap Team hosted by OpenStreetMap France',
          maxZoom: 19
        }).addTo(this.map);
      }
    }
  }

  // Fallback method if Google Maps is not available
  loadMapFallback() {
    console.log('Google Maps not loaded, using fallback map');
    this.mapInitialized = true;
    
    // Create a simple fallback map display
    const mapElement = document.getElementById('map');
    if (mapElement) {
      mapElement.innerHTML = `
        <div style="
          width: 100%;
          height: 100%;
          background: linear-gradient(135deg, #e3f2fd, #bbdefb);
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          position: relative;
          font-family: Arial, sans-serif;
        ">
          <div style="
            background: rgba(255,255,255,0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
          ">
            <h3 style="margin: 0 0 10px 0; color: #1976d2;">University of Baguio Campus</h3>
            <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">
              Interactive map unavailable. Showing incident locations below.
            </p>
            <div style="text-align: left;">
              <h4 style="margin: 0 0 10px 0; color: #333; font-size: 14px;">Campus Buildings:</h4>
              <ul style="margin: 0 0 15px 0; padding-left: 20px; color: #666; font-size: 13px;">
                <li>Main Building</li>
                <li>University Library</li>
                <li>Gymnasium</li>
                <li>Administration Building</li>
                <li>Parking Areas</li>
              </ul>
              <h4 style="margin: 0 0 10px 0; color: #333; font-size: 14px;">Incidents by severity:</h4>
              <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                ${this.markers.map(marker => `
                  <div style="
                    background: ${this.getSeverityColor(marker.incident.severity)};
                    color: white;
                    padding: 4px 8px;
                    border-radius: 12px;
                    font-size: 11px;
                    font-weight: bold;
                  ">
                    ${marker.incident.severity.toUpperCase()}
                  </div>
                `).join('')}
              </div>
            </div>
          </div>
          <div style="position: absolute; bottom: 20px; left: 20px; right: 20px;">
            <div style="background: rgba(255,255,255,0.9); padding: 15px; border-radius: 8px;">
              <h4 style="margin: 0 0 10px 0; color: #333; font-size: 14px;">Recent Incidents:</h4>
              ${this.markers.slice(0, 3).map(marker => `
                <div style="margin-bottom: 8px; padding: 8px; background: #f5f5f5; border-radius: 4px;">
                  <strong style="color: #333;">${marker.incident.title}</strong>
                  <br>
                  <span style="color: #666; font-size: 12px;">${marker.incident.location.address}</span>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      `;
    }
  }
}
