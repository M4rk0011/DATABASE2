import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, of } from 'rxjs';
import {
  Concern,
  ConcernCategory,
  ConcernSeverity,
  ConcernStatus,
  Location,
  ContactInfo,
  MapMarker,
  AdminStats
} from '../models/concern.model';

@Injectable({
  providedIn: 'root'
})
export class ConcernService {
  private concerns: Concern[] = [];
  private concernsSubject = new BehaviorSubject<Concern[]>([]);
  private apiUrl = 'api/concerns'; // Base API URL for concerns
  private httpOptions = {
    headers: new HttpHeaders({
      'Content-Type': 'application/json',
      'Access-Control-Allow-Origin': '*'
    })
  };

  constructor(private http: HttpClient) {
    this.initializeMockConcerns();
  }

  private initializeMockConcerns() {
    this.concerns = [
      {
        id: '1',
        title: 'Theft at Library',
        description: 'Student reported laptop stolen from study area',
        category: ConcernCategory.THEFT,
        severity: ConcernSeverity.HIGH,
        status: ConcernStatus.INVESTIGATING,
        location: {
          latitude: 16.4158,
          longitude: 120.5965,
          address: 'University of Baguio Main Library',
          building: 'Main Library',
          floor: '2nd Floor',
          room: 'Study Area A'
        },
        reportedBy: 'student',
        reportedAt: new Date('2024-04-10T10:30:00'),
        updatedAt: new Date('2024-04-10T14:15:00'),
        assignedTo: 'security',
        images: [],
        witnesses: ['John Doe', 'Jane Smith'],
        contactInfo: {
          name: 'Maria Santos',
          email: 'student@ub.edu.ph',
          phone: '+63-777-777-7777',
          studentId: '2024-12345',
          department: 'College of Engineering'
        },
        additionalNotes: 'Laptop was left unattended for 5 minutes',
        isAnonymous: false
      },
      {
        id: '2',
        title: 'Vandalism in Parking Lot',
        description: 'Car windows broken in faculty parking area',
        category: ConcernCategory.VANDALISM,
        severity: ConcernSeverity.MEDIUM,
        status: ConcernStatus.REPORTED,
        location: {
          latitude: 16.4159,
          longitude: 120.5961,
          address: 'Faculty Parking Lot',
          building: 'Administration Building',
          landmark: 'Near main entrance'
        },
        reportedBy: 'admin',
        reportedAt: new Date('2024-04-11T08:45:00'),
        updatedAt: new Date('2024-04-11T08:45:00'),
        images: [],
        witnesses: [],
        contactInfo: {
          name: 'John Smith',
          email: 'security@ub.edu.ph',
          phone: '+63-888-888-8888'
        },
        isAnonymous: false
      },
      {
        id: '3',
        title: 'Medical Emergency - Gym',
        description: 'Student fainted during basketball practice',
        category: ConcernCategory.MEDICAL,
        severity: ConcernSeverity.HIGH,
        status: ConcernStatus.RESOLVED,
        location: {
          latitude: 16.4153,
          longitude: 120.5988,
          address: 'University Gymnasium',
          building: 'Sports Complex',
          floor: 'Ground Floor'
        },
        reportedBy: 'security',
        reportedAt: new Date('2024-04-09T16:20:00'),
        updatedAt: new Date('2024-04-09T17:30:00'),
        resolvedAt: new Date('2024-04-09T17:30:00'),
        assignedTo: 'admin',
        images: [],
        witnesses: ['Coach Johnson'],
        contactInfo: {
          name: 'Anonymous Reporter',
          email: 'anonymous@ub.edu.ph'
        },
        additionalNotes: 'Student treated by campus medical team',
        isAnonymous: true
      }
    ];

    this.concernsSubject.next(this.concerns);
  }

  getConcerns(): Observable<Concern[]> {
    return of(this.concerns);
  }

  getConcernById(id: string): Observable<Concern> {
    const concern = this.concerns.find(i => i.id === id);
    if (!concern) {
      throw new Error(`Concern with ID ${id} not found`);
    }
    return of(concern);
  }

  createConcern(concernData: Omit<Concern, 'id' | 'reportedAt' | 'updatedAt'>): Observable<Concern> {
    // Validate that the concern is within University of Baguio campus boundaries
    if (!this.isWithinCampusBounds(concernData.location.latitude, concernData.location.longitude)) {
      console.error('Concern location is outside University of Baguio campus boundaries');
      throw new Error('Concern location is outside University of Baguio campus boundaries');
    }

    const newConcern: Concern = {
      ...concernData,
      id: (this.concerns.length + 1).toString(),
      reportedAt: new Date(),
      updatedAt: new Date()
    };

    this.concerns.push(newConcern);
    this.concernsSubject.next(this.concerns);
    return of(newConcern);
  }

  private isWithinCampusBounds(latitude: number, longitude: number): boolean {
    // Same as new-report map (spans are 3/4 of the prior rectangle; centered on official UB point)
    const southwest: [number, number] = [16.414543, 120.595925];
    const northeast: [number, number] = [16.416568, 120.599075];

    return (
      latitude >= southwest[0] &&
      latitude <= northeast[0] &&
      longitude >= southwest[1] &&
      longitude <= northeast[1]
    );
  }

  updateConcern(id: string, updates: Partial<Concern>): Observable<Concern> {
    const concernIndex = this.concerns.findIndex(i => i.id === id);
    if (concernIndex === -1) {
      throw new Error(`Concern with ID ${id} not found`);
    }

    this.concerns[concernIndex] = {
      ...this.concerns[concernIndex],
      ...updates,
      updatedAt: new Date()
    };

    this.concernsSubject.next(this.concerns);
    return of(this.concerns[concernIndex]);
  }

  deleteConcern(id: string): Observable<void> {
    const concernIndex = this.concerns.findIndex(i => i.id === id);
    if (concernIndex === -1) {
      throw new Error(`Concern with ID ${id} not found`);
    }

    this.concerns.splice(concernIndex, 1);
    this.concernsSubject.next(this.concerns);
    return of(void 0);
  }

  getConcernsByCategory(category: ConcernCategory): Observable<Concern[]> {
    const filteredConcerns = this.concerns.filter(i => i.category === category);
    return of(filteredConcerns);
  }

  getConcernsByStatus(status: ConcernStatus): Observable<Concern[]> {
    const filteredConcerns = this.concerns.filter(i => i.status === status);
    return of(filteredConcerns);
  }

  getConcernsByReporter(reporterId: string): Observable<Concern[]> {
    const filteredConcerns = this.concerns.filter(i => i.reportedBy === reporterId);
    return of(filteredConcerns);
  }

  getMapMarkers(): Observable<MapMarker[]> {
    const markers: MapMarker[] = this.concerns.map(concern => ({
      id: concern.id,
      position: {
        lat: concern.location.latitude,
        lng: concern.location.longitude
      },
      title: concern.title,
      concern: concern,
      icon: this.getMarkerIcon(concern.severity)
    }));

    return of(markers);
  }

  private getMarkerIcon(severity: ConcernSeverity): string {
    switch (severity) {
      case ConcernSeverity.CRITICAL:
        return 'red';
      case ConcernSeverity.HIGH:
        return 'orange';
      case ConcernSeverity.MEDIUM:
        return 'yellow';
      case ConcernSeverity.LOW:
        return 'green';
      default:
        return 'blue';
    }
  }

  getAdminStats(): AdminStats {
    const totalConcerns = this.concerns.length;
    const openConcerns = this.concerns.filter(i =>
      i.status === ConcernStatus.REPORTED ||
      i.status === ConcernStatus.INVESTIGATING
    ).length;
    const resolvedConcerns = this.concerns.filter(i =>
      i.status === ConcernStatus.RESOLVED
    ).length;
    const criticalConcerns = this.concerns.filter(i =>
      i.severity === ConcernSeverity.CRITICAL
    ).length;

    const concernsByCategory = this.concerns.reduce((acc, concern) => {
      acc[concern.category] = (acc[concern.category] || 0) + 1;
      return acc;
    }, {} as { [key: string]: number });

    const concernsBySeverity = this.concerns.reduce((acc, concern) => {
      acc[concern.severity] = (acc[concern.severity] || 0) + 1;
      return acc;
    }, {} as { [key: string]: number });

    const resolvedConcernsWithTime = this.concerns.filter(i =>
      i.status === ConcernStatus.RESOLVED && i.resolvedAt
    );

    const averageResolutionTime = resolvedConcernsWithTime.length > 0
      ? resolvedConcernsWithTime.reduce((sum, concern) => {
          const resolutionTime = concern.resolvedAt!.getTime() - concern.reportedAt.getTime();
          return sum + resolutionTime;
        }, 0) / resolvedConcernsWithTime.length / (1000 * 60 * 60) // Convert to hours
      : 0;

    const currentMonth = new Date().getMonth();
    const currentYear = new Date().getFullYear();
    const concernsThisMonth = this.concerns.filter(i => {
      const concernDate = new Date(i.reportedAt);
      return concernDate.getMonth() === currentMonth &&
             concernDate.getFullYear() === currentYear;
    }).length;

    return {
      totalConcerns,
      openConcerns,
      resolvedConcerns,
      criticalConcerns,
      concernsByCategory,
      concernsBySeverity,
      averageResolutionTime,
      concernsThisMonth
    };
  }

  searchConcerns(query: string): Observable<Concern[]> {
    const searchLower = query.toLowerCase();
    const filteredConcerns = this.concerns.filter(i =>
      i.title.toLowerCase().includes(searchLower) ||
      i.description.toLowerCase().includes(searchLower) ||
      i.location.address.toLowerCase().includes(searchLower)
    );
    return of(filteredConcerns);
  }
}
