import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { ConcernService } from '../../services/concern.service';
import { AuthService } from '../../services/auth.service';
import { PdfService } from '../../services/pdf.service';
import { Concern, ConcernStatus, ConcernSeverity, User } from '../../models/concern.model';

@Component({
  selector: 'app-concern-details',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './concern-details.component.html',
  styleUrls: ['./concern-details.component.css']
})
export class ConcernDetailsComponent implements OnInit {
  concern: Concern | undefined;
  isLoading = true;
  isAdmin = false;
  isSecurity = false;
  currentUser: User | null = null;
  ConcernStatus = ConcernStatus;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private concernService: ConcernService,
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
        this.loadConcern(id);
      }
    });
  }

  loadConcern(id: string) {
    this.isLoading = true;
    this.concernService.getConcernById(id).subscribe({
      next: data => {
        this.concern = data;
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
    if (!this.canEditConcernRecord()) {
      return;
    }
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: {},
      replaceUrl: true
    });
    queueMicrotask(() => this.editConcern());
  }

  updateStatus(newStatus: ConcernStatus) {
    if (this.concern) {
      this.concernService.updateConcern(this.concern.id, { status: newStatus }).subscribe(() => {
        this.loadConcern(this.concern!.id);
      });
    }
  }

  deleteConcern() {
    if (this.concern && confirm('Are you sure you want to delete this concern?')) {
      this.concernService.deleteConcern(this.concern.id).subscribe(() => {
        this.router.navigate(['/concerns']);
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
  canEditConcernRecord(): boolean {
    if (!this.concern) {
      return false;
    }
    if (this.isAdmin || this.isSecurity) {
      return true;
    }
    const u = this.currentUser;
    if (!u) {
      return false;
    }
    return this.concern.reportedBy === u.id || this.concern.reportedBy === u.username;
  }

  canDeleteConcern(): boolean {
    return this.isAdmin;
  }

  goBack() {
    this.router.navigate(['/concerns']);
  }

  editConcern() {
    if (!this.concern || !this.canEditConcernRecord()) {
      return;
    }
    const title = window.prompt('Concern title', this.concern.title);
    if (title === null) {
      return;
    }
    const trimmedTitle = title.trim();
    if (!trimmedTitle) {
      alert('Title cannot be empty.');
      return;
    }
    const description = window.prompt('Description', this.concern.description);
    if (description === null) {
      return;
    }
    const trimmedDescription = description.trim();
    if (!trimmedDescription) {
      alert('Description cannot be empty.');
      return;
    }
    this.concernService
      .updateConcern(this.concern.id, {
        title: trimmedTitle,
        description: trimmedDescription
      })
      .subscribe({
        next: () => this.loadConcern(this.concern!.id),
        error: err => {
          console.error(err);
          alert(err?.message ?? 'Could not save changes.');
        }
      });
  }

  printConcern() {
    if (this.concern) {
      this.pdfService.generateConcernPDF(this.concern);
    }
  }

  downloadConcernPDF() {
    console.log('PDF download button clicked');
    if (this.concern) {
      console.log('Concern data available:', this.concern.id);
      this.pdfService.downloadConcernPDF(this.concern);
    } else {
      console.error('No concern data available');
      alert('No concern data available. Please try again.');
    }
  }

  shareConcernReport() {
    if (this.concern) {
      this.pdfService.shareConcernReport(this.concern);
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
