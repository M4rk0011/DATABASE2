import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { IncidentService } from '../../services/incident.service';
import { AuthService } from '../../services/auth.service';
import { Incident, IncidentStatus, IncidentSeverity, User } from '../../models/incident.model';
import { Observable, of } from 'rxjs';

@Component({
  selector: 'app-incident-list',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './incident-list.component.html',
  styleUrls: ['./incident-list.component.css']
})
export class IncidentListComponent implements OnInit {
  incidents$: Observable<Incident[]> = of([]);
  statusFilter = '';
  severityFilter = '';
  searchTerm = '';
  isLoading = false;
  isAdmin = false;
  currentUser: User | null = null;

  statuses = Object.values(IncidentStatus);
  severities = Object.values(IncidentSeverity);

  constructor(
    private incidentService: IncidentService,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit() {
    this.authService.getCurrentUser().subscribe(user => {
      this.currentUser = user;
      this.isAdmin = this.authService.isAdmin();
    });
    this.loadIncidents();
  }

  /** Admin/security, or the user who filed the report (`reportedBy` is user id or username in mock data). */
  canEditIncidentContent(incident: Incident): boolean {
    if (this.isAdmin || this.authService.isSecurity()) {
      return true;
    }
    const u = this.currentUser;
    if (!u) {
      return false;
    }
    return incident.reportedBy === u.id || incident.reportedBy === u.username;
  }

  goToEditIncident(incident: Incident, event: Event): void {
    event.preventDefault();
    event.stopPropagation();
    this.router.navigate(['/incidents', incident.id], { queryParams: { edit: '1' } });
  }

  loadIncidents() {
    this.isLoading = true;
    this.incidents$ = this.incidentService.getIncidents();
    this.incidents$.subscribe(data => {
      this.applyFilters();
      this.isLoading = false;
    });
  }

  applyFilters() {
    // Filtering will be handled by the template with async pipe
  }

  onFilterChange() {
    this.applyFilters();
  }

  updateIncidentStatus(incidentId: string, newStatus: IncidentStatus) {
    this.incidentService.updateIncident(incidentId, { status: newStatus }).subscribe(() => {
      this.loadIncidents();
    });
  }

  deleteIncident(incidentId: string) {
    if (confirm('Are you sure you want to delete this incident?')) {
      this.incidentService.deleteIncident(incidentId).subscribe(() => {
        this.loadIncidents();
      });
    }
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

  canUpdateStatus(): boolean {
    return this.isAdmin || this.authService.isSecurity();
  }

  canDeleteIncident(): boolean {
    return this.isAdmin;
  }
}
