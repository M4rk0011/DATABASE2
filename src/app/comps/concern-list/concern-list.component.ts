import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ConcernService } from '../../services/concern.service';
import { AuthService } from '../../services/auth.service';
import { Concern, ConcernStatus, ConcernSeverity, User } from '../../models/concern.model';
import { Observable, of } from 'rxjs';

@Component({
  selector: 'app-concern-list',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './concern-list.component.html',
  styleUrls: ['./concern-list.component.css']
})
export class ConcernListComponent implements OnInit {
  concerns$: Observable<Concern[]> = of([]);
  statusFilter = '';
  severityFilter = '';
  searchTerm = '';
  isLoading = false;
  isAdmin = false;
  currentUser: User | null = null;

  statuses = Object.values(ConcernStatus);
  severities = Object.values(ConcernSeverity);

  constructor(
    private concernService: ConcernService,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit() {
    this.authService.getCurrentUser().subscribe(user => {
      this.currentUser = user;
      this.isAdmin = this.authService.isAdmin();
    });
    this.loadConcerns();
  }

  /** Admin/security, or the user who filed the report (`reportedBy` is user id or username in mock data). */
  canEditConcernContent(concern: Concern): boolean {
    if (this.isAdmin || this.authService.isSecurity()) {
      return true;
    }
    const u = this.currentUser;
    if (!u) {
      return false;
    }
    return concern.reportedBy === u.id || concern.reportedBy === u.username;
  }

  goToEditConcern(concern: Concern, event: Event): void {
    event.preventDefault();
    event.stopPropagation();
    this.router.navigate(['/concerns', concern.id], { queryParams: { edit: '1' } });
  }

  loadConcerns() {
    this.isLoading = true;
    this.concerns$ = this.concernService.getConcerns();
    this.concerns$.subscribe(data => {
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

  updateConcernStatus(concernId: string, newStatus: ConcernStatus) {
    this.concernService.updateConcern(concernId, { status: newStatus }).subscribe(() => {
      this.loadConcerns();
    });
  }

  deleteConcern(concernId: string) {
    if (confirm('Are you sure you want to delete this concern?')) {
      this.concernService.deleteConcern(concernId).subscribe(() => {
        this.loadConcerns();
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

  canDeleteConcern(): boolean {
    return this.isAdmin;
  }
}
