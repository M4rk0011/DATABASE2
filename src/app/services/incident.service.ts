import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { 
  Incident, 
  IncidentCategory, 
  IncidentSeverity, 
  IncidentStatus, 
  Location, 
  ContactInfo,
  MapMarker,
  AdminStats
} from '../models/incident.model';

@Injectable({
  providedIn: 'root'
})
export class IncidentService {
  private incidents: Incident[] = [];
  private incidentsSubject = new BehaviorSubject<Incident[]>([]);
  private apiUrl = 'api/incidents'; // Base API URL for incidents
  private httpOptions = {
    headers: new HttpHeaders({
      'Content-Type': 'application/json',
      'Access-Control-Allow-Origin': '*'
    })
  };

  constructor(private http: HttpClient) {
    this.initializeMockIncidents();
  }

  private initializeMockIncidents() {
    this.incidents = [
      {
        id: '1',
        title: 'Theft at Library',
        description: 'Student reported laptop stolen from study area',
        category: IncidentCategory.THEFT,
        severity: IncidentSeverity.HIGH,
        status: IncidentStatus.INVESTIGATING,
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
        category: IncidentCategory.VANDALISM,
        severity: IncidentSeverity.MEDIUM,
        status: IncidentStatus.REPORTED,
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
        category: IncidentCategory.MEDICAL,
        severity: IncidentSeverity.HIGH,
        status: IncidentStatus.RESOLVED,
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

    this.incidentsSubject.next(this.incidents);
  }

  getIncidents(): Observable<Incident[]> {
    return of(this.incidents);
  }

  getIncidentById(id: string): Observable<Incident> {
    const incident = this.incidents.find(i => i.id === id);
    if (!incident) {
      throw new Error(`Incident with ID ${id} not found`);
    }
    return of(incident);
  }

  createIncident(incidentData: Omit<Incident, 'id' | 'reportedAt' | 'updatedAt'>): Observable<Incident> {
    // Validate that the incident is within University of Baguio campus boundaries
    if (!this.isWithinCampusBounds(incidentData.location.latitude, incidentData.location.longitude)) {
      console.error('Incident location is outside University of Baguio campus boundaries');
      throw new Error('Incident location is outside University of Baguio campus boundaries');
    }

    const newIncident: Incident = {
      ...incidentData,
      id: (this.incidents.length + 1).toString(),
      reportedAt: new Date(),
      updatedAt: new Date()
    };

    this.incidents.push(newIncident);
    this.incidentsSubject.next(this.incidents);
    return of(newIncident);
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

  updateIncident(id: string, updates: Partial<Incident>): Observable<Incident> {
    const incidentIndex = this.incidents.findIndex(i => i.id === id);
    if (incidentIndex === -1) {
      throw new Error(`Incident with ID ${id} not found`);
    }

    this.incidents[incidentIndex] = {
      ...this.incidents[incidentIndex],
      ...updates,
      updatedAt: new Date()
    };

    this.incidentsSubject.next(this.incidents);
    return of(this.incidents[incidentIndex]);
  }

  deleteIncident(id: string): Observable<void> {
    const incidentIndex = this.incidents.findIndex(i => i.id === id);
    if (incidentIndex === -1) {
      throw new Error(`Incident with ID ${id} not found`);
    }

    this.incidents.splice(incidentIndex, 1);
    this.incidentsSubject.next(this.incidents);
    return of(void 0);
  }

  getIncidentsByCategory(category: IncidentCategory): Observable<Incident[]> {
    const filteredIncidents = this.incidents.filter(i => i.category === category);
    return of(filteredIncidents);
  }

  getIncidentsByStatus(status: IncidentStatus): Observable<Incident[]> {
    const filteredIncidents = this.incidents.filter(i => i.status === status);
    return of(filteredIncidents);
  }

  getIncidentsByReporter(reporterId: string): Observable<Incident[]> {
    const filteredIncidents = this.incidents.filter(i => i.reportedBy === reporterId);
    return of(filteredIncidents);
  }

  getMapMarkers(): Observable<MapMarker[]> {
    const markers: MapMarker[] = this.incidents.map(incident => ({
      id: incident.id,
      position: {
        lat: incident.location.latitude,
        lng: incident.location.longitude
      },
      title: incident.title,
      incident: incident,
      icon: this.getMarkerIcon(incident.severity)
    }));

    return of(markers);
  }

  private getMarkerIcon(severity: IncidentSeverity): string {
    switch (severity) {
      case IncidentSeverity.CRITICAL:
        return 'red';
      case IncidentSeverity.HIGH:
        return 'orange';
      case IncidentSeverity.MEDIUM:
        return 'yellow';
      case IncidentSeverity.LOW:
        return 'green';
      default:
        return 'blue';
    }
  }

  getAdminStats(): AdminStats {
    const totalIncidents = this.incidents.length;
    const openIncidents = this.incidents.filter(i => 
      i.status === IncidentStatus.REPORTED || 
      i.status === IncidentStatus.INVESTIGATING
    ).length;
    const resolvedIncidents = this.incidents.filter(i => 
      i.status === IncidentStatus.RESOLVED
    ).length;
    const criticalIncidents = this.incidents.filter(i => 
      i.severity === IncidentSeverity.CRITICAL
    ).length;

    const incidentsByCategory = this.incidents.reduce((acc, incident) => {
      acc[incident.category] = (acc[incident.category] || 0) + 1;
      return acc;
    }, {} as { [key: string]: number });

    const incidentsBySeverity = this.incidents.reduce((acc, incident) => {
      acc[incident.severity] = (acc[incident.severity] || 0) + 1;
      return acc;
    }, {} as { [key: string]: number });

    const resolvedIncidentsWithTime = this.incidents.filter(i => 
      i.status === IncidentStatus.RESOLVED && i.resolvedAt
    );

    const averageResolutionTime = resolvedIncidentsWithTime.length > 0 
      ? resolvedIncidentsWithTime.reduce((sum, incident) => {
          const resolutionTime = incident.resolvedAt!.getTime() - incident.reportedAt.getTime();
          return sum + resolutionTime;
        }, 0) / resolvedIncidentsWithTime.length / (1000 * 60 * 60) // Convert to hours
      : 0;

    const currentMonth = new Date().getMonth();
    const currentYear = new Date().getFullYear();
    const incidentsThisMonth = this.incidents.filter(i => {
      const incidentDate = new Date(i.reportedAt);
      return incidentDate.getMonth() === currentMonth && 
             incidentDate.getFullYear() === currentYear;
    }).length;

    return {
      totalIncidents,
      openIncidents,
      resolvedIncidents,
      criticalIncidents,
      incidentsByCategory,
      incidentsBySeverity,
      averageResolutionTime,
      incidentsThisMonth
    };
  }

  searchIncidents(query: string): Observable<Incident[]> {
    const searchLower = query.toLowerCase();
    const filteredIncidents = this.incidents.filter(i => 
      i.title.toLowerCase().includes(searchLower) ||
      i.description.toLowerCase().includes(searchLower) ||
      i.location.address.toLowerCase().includes(searchLower)
    );
    return of(filteredIncidents);
  }
}
