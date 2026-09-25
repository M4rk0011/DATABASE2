import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { IncidentService } from '../../services/incident.service';
import { User, UserRole } from '../../models/incident.model';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  currentUser: User | null = null;
  stats: any = {};
  recentIncidents: any[] = [];
  users: User[] = [];
  editingUserId: string | null = null;
  editUserForm = {
    username: '',
    email: '',
    password: '',
    role: UserRole.USER,
    firstName: '',
    lastName: '',
    phone: '',
    studentId: '',
    department: '',
    position: ''
  };
  userActionMessage = '';
  userActionError = '';
  UserRole = UserRole;

  constructor(
    private authService: AuthService,
    private incidentService: IncidentService,
    private router: Router
  ) {}

  ngOnInit() {
    this.authService.getCurrentUser().subscribe(user => {
      this.currentUser = user;
      if (!user) {
        this.router.navigate(['/login']);
        return;
      }
      this.loadDashboardData();
    });
  }

  private loadDashboardData() {
    if (this.authService.isAdmin()) {
      this.loadAdminData();
    } else {
      this.loadUserData();
    }
  }

  private loadAdminData() {
    this.stats = this.incidentService.getAdminStats();
    this.incidentService.getIncidents().subscribe(incidents => {
      this.recentIncidents = incidents
        .sort((a, b) => new Date(b.reportedAt).getTime() - new Date(a.reportedAt).getTime())
        .slice(0, 5);
    });
    this.loadUsers();
  }

  private loadUserData() {
    if (this.currentUser) {
      this.incidentService.getIncidentsByReporter(this.currentUser.id).subscribe(incidents => {
        this.stats = {
          totalIncidents: incidents.length,
          openIncidents: incidents.filter(i => i.status === 'reported').length,
          resolvedIncidents: incidents.filter(i => i.status === 'resolved').length
        };
        this.recentIncidents = incidents
          .sort((a, b) => new Date(b.reportedAt).getTime() - new Date(a.reportedAt).getTime())
          .slice(0, 5);
      });
    }
  }

  logout() {
    this.authService.logout();
    this.router.navigate(['/login']);
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

  viewIncident(incidentId: string) {
    this.router.navigate(['/incidents', incidentId]);
  }

  createIncident() {
    this.router.navigate(['/report']);
  }

  viewMap() {
    this.router.navigate(['/map']);
  }

  goToUserManagement() {
    const section = document.getElementById('user-management-section');
    if (section) {
      section.scrollIntoView({ behavior: 'smooth', block: 'start' });
      this.loadUsers();
    }
  }

  viewAllIncidents() {
    this.router.navigate(['/incidents']);
  }

  isAdmin(): boolean {
    return this.authService.isAdmin();
  }

  loadUsers() {
    this.authService.getAllUsers().subscribe(users => {
      this.users = users;
    });
  }

  isAdminUser(user: User): boolean {
    return user.username === 'admin' || user.role === UserRole.ADMIN;
  }

  startEditUser(user: User) {
    this.userActionMessage = '';
    this.userActionError = '';
    this.editingUserId = user.id;
    this.editUserForm = {
      username: user.username,
      email: user.email,
      password: user.password,
      role: user.role,
      firstName: user.profile.firstName || '',
      lastName: user.profile.lastName || '',
      phone: user.profile.phone || '',
      studentId: user.profile.studentId || '',
      department: user.profile.department || '',
      position: user.profile.position || ''
    };
  }

  cancelEditUser() {
    this.editingUserId = null;
    this.userActionError = '';
  }

  saveUserChanges(userId: string) {
    if (!this.editUserForm.username || !this.editUserForm.email || !this.editUserForm.password) {
      this.userActionError = 'Username, email, and password are required.';
      return;
    }

    this.authService.updateUser(userId, {
      username: this.editUserForm.username.trim(),
      email: this.editUserForm.email.trim(),
      password: this.editUserForm.password,
      role: this.editUserForm.role,
      profile: {
        firstName: this.editUserForm.firstName.trim(),
        lastName: this.editUserForm.lastName.trim(),
        phone: this.editUserForm.phone.trim(),
        studentId: this.editUserForm.studentId.trim(),
        department: this.editUserForm.department.trim(),
        position: this.editUserForm.position.trim()
      }
    }).subscribe(success => {
      if (!success) {
        this.userActionError = 'Unable to update user. Check for duplicate username/email or restricted user.';
        return;
      }

      this.userActionError = '';
      this.userActionMessage = 'User updated successfully.';
      this.editingUserId = null;
      this.loadUsers();
    });
  }

  deleteUser(user: User) {
    this.userActionMessage = '';
    this.userActionError = '';

    if (this.isAdminUser(user)) {
      this.userActionError = 'Admin user cannot be deleted.';
      return;
    }

    const confirmed = window.confirm(`Delete user "${user.username}"?`);
    if (!confirmed) return;

    this.authService.deleteUser(user.id).subscribe(success => {
      if (!success) {
        this.userActionError = 'Unable to delete user.';
        return;
      }

      this.userActionMessage = 'User deleted successfully.';
      if (this.editingUserId === user.id) {
        this.editingUserId = null;
      }
      this.loadUsers();
    });
  }

  isSecurity(): boolean {
    return this.authService.isSecurity();
  }

  getCategoryDisplayName(key: any): string {
    return (key as string).replace('_', ' ').toUpperCase();
  }

  getSeverityDisplayName(key: any): string {
    return (key as string).toUpperCase();
  }

  getSeverityColorFromKey(key: any): string {
    return this.getSeverityColor(key as string);
  }
}
