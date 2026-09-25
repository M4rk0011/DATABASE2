import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { IncidentService } from '../../services/incident.service';
import { AuthService } from '../../services/auth.service';
import { PdfService } from '../../services/pdf.service';
import { Incident, IncidentStatus, IncidentSeverity, User } from '../../models/incident.model';

@Component({
  selector: 'app-incident-details',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './incident-details.component.html',
  styleUrls: ['./incident-details.component.css']
})
export class IncidentDetailsComponent implements OnInit {
  incident: Incident | undefined;
  isLoading = true;
  isAdmin = false;
  isSecurity = false;
  currentUser: User | null = null;
  IncidentStatus = IncidentStatus;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private incidentService: IncidentService,
    private authService: AuthService,
    private pdfService: PdfService
  ) {}

  ngOnInit() {
    this.authService.getCurrentUser().subscribe(user => {
      this.currentUser = user;
      this.isAdmin = this.authService.isAdmin();
      this.isSecurity = this.authService.isSecurity();
      const id = this.route.snapshot.paramMap.get('id');
      if (id) {
        this.loadIncident(id);
      }
    });
  }

  loadIncident(id: string) {
    this.isLoading = true;
    this.incidentService.getIncidentById(id).subscribe({
      next: data => {
        this.incident = data;
        this.isLoading = false;
        this.maybeStartEditFromQuery();
      },
      error: () => {
        this.isLoading = false;
      }
    });
  }

  private maybeStartEditFromQuery(): void {
    if (this.route.snapshot.queryParamMap.get('edit') !== '1') {
      return;
    }
    if (!this.canEditIncidentRecord()) {
      return;
    }
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: {},
      replaceUrl: true
    });
    queueMicrotask(() => this.editIncident());
  }

  updateStatus(newStatus: IncidentStatus) {
    if (this.incident) {
      this.incidentService.updateIncident(this.incident.id, { status: newStatus }).subscribe(() => {
        this.loadIncident(this.incident!.id);
      });
    }
  }

  deleteIncident() {
    if (this.incident && confirm('Are you sure you want to delete this incident?')) {
      this.incidentService.deleteIncident(this.incident.id).subscribe(() => {
        this.router.navigate(['/incidents']);
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
    return this.isAdmin || this.isSecurity;
  }

  /** Who may change title/description: staff or original reporter. */
  canEditIncidentRecord(): boolean {
    if (!this.incident) {
      return false;
    }
    if (this.isAdmin || this.isSecurity) {
      return true;
    }
    const u = this.currentUser;
    if (!u) {
      return false;
    }
    return this.incident.reportedBy === u.id || this.incident.reportedBy === u.username;
  }

  canDeleteIncident(): boolean {
    return this.isAdmin;
  }

  goBack() {
    this.router.navigate(['/incidents']);
  }

  editIncident() {
    if (!this.incident || !this.canEditIncidentRecord()) {
      return;
    }
    const title = window.prompt('Incident title', this.incident.title);
    if (title === null) {
      return;
    }
    const trimmedTitle = title.trim();
    if (!trimmedTitle) {
      alert('Title cannot be empty.');
      return;
    }
    const description = window.prompt('Description', this.incident.description);
    if (description === null) {
      return;
    }
    const trimmedDescription = description.trim();
    if (!trimmedDescription) {
      alert('Description cannot be empty.');
      return;
    }
    this.incidentService
      .updateIncident(this.incident.id, {
        title: trimmedTitle,
        description: trimmedDescription
      })
      .subscribe({
        next: () => this.loadIncident(this.incident!.id),
        error: err => {
          console.error(err);
          alert(err?.message ?? 'Could not save changes.');
        }
      });
  }

  printIncident() {
    if (this.incident) {
      this.pdfService.generateIncidentPDF(this.incident);
    }
  }

  downloadIncidentPDF() {
    console.log('PDF download button clicked');
    if (this.incident) {
      console.log('Incident data available:', this.incident.id);
      this.pdfService.downloadIncidentPDF(this.incident);
    } else {
      console.error('No incident data available');
      alert('No incident data available. Please try again.');
    }
  }

  shareIncidentReport() {
    if (this.incident) {
      this.pdfService.shareIncidentReport(this.incident);
    }
  }

  getTimeElapsed(reportDate: Date): string {
    const now = new Date();
    const reported = new Date(reportDate);
    const diffInHours = Math.floor((now.getTime() - reported.getTime()) / (1000 * 60 * 60));
    
    if (diffInHours < 1) {
      return 'Less than 1 hour';
    } else if (diffInHours < 24) {
      return `${diffInHours} hour${diffInHours > 1 ? 's' : ''}`;
    } else {
      const diffInDays = Math.floor(diffInHours / 24);
      return `${diffInDays} day${diffInDays > 1 ? 's' : ''}`;
    }
  }
}
