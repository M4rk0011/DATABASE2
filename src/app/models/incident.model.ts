export interface Incident {
  id: string;
  title: string;
  description: string;
  category: IncidentCategory;
  severity: IncidentSeverity;
  status: IncidentStatus;
  location: Location;
  reportedBy: string;
  reportedAt: Date;
  updatedAt: Date;
  resolvedAt?: Date;
  assignedTo?: string;
  images: string[];
  witnesses: string[];
  contactInfo: ContactInfo;
  additionalNotes?: string;
  isAnonymous: boolean;
}

export interface Location {
  latitude: number;
  longitude: number;
  address: string;
  building?: string;
  floor?: string;
  room?: string;
  landmark?: string;
}

export interface ContactInfo {
  name: string;
  email: string;
  phone?: string;
  studentId?: string;
  department?: string;
}

export interface User {
  id: string;
  username: string;
  email: string;
  password: string;
  role: UserRole;
  profile: UserProfile;
  createdAt: Date;
  lastLogin?: Date;
  isActive: boolean;
}

export interface UserProfile {
  firstName: string;
  lastName: string;
  phone?: string;
  studentId?: string;
  department?: string;
  position?: string;
  avatar?: string;
}

export interface AdminStats {
  totalIncidents: number;
  openIncidents: number;
  resolvedIncidents: number;
  criticalIncidents: number;
  incidentsByCategory: { [key: string]: number };
  incidentsBySeverity: { [key: string]: number };
  averageResolutionTime: number;
  incidentsThisMonth: number;
}

export enum IncidentCategory {
  THEFT = 'theft',
  VANDALISM = 'vandalism',
  HARASSMENT = 'harassment',
  ACCIDENT = 'accident',
  MEDICAL = 'medical',
  FIRE = 'fire',
  SECURITY = 'security',
  FACILITY = 'facility',
  TECHNOLOGY = 'technology',
  OTHER = 'other'
}

export enum IncidentSeverity {
  LOW = 'low',
  MEDIUM = 'medium',
  HIGH = 'high',
  CRITICAL = 'critical'
}

export enum IncidentStatus {
  REPORTED = 'reported',
  INVESTIGATING = 'investigating',
  RESOLVED = 'resolved',
  CLOSED = 'closed',
  FALSE_ALARM = 'false_alarm'
}

export enum UserRole {
  ADMIN = 'admin',
  USER = 'user',
  SECURITY = 'security'
}

export interface MapMarker {
  id: string;
  position: {
    lat: number;
    lng: number;
  };
  title: string;
  incident: Incident;
  icon: string;
}
