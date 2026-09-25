import { Component, OnInit } from '@angular/core';
import { Router, RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from './services/auth.service';

@Component({
  selector: 'app-root',
  standalone: true,
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css'],
  imports: [CommonModule, RouterOutlet, RouterLink, RouterLinkActive]
})
export class AppComponent implements OnInit {
  currentUser: any = null;
  isAuthenticated = false;
  currentUrl = '';
  UserRole = {
    ADMIN: 'admin',
    SECURITY: 'security',
    USER: 'user'
  };

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit() {
    // Set initial URL and listen for router events
    this.currentUrl = this.router.url;
    this.router.events.subscribe(() => {
      this.currentUrl = this.router.url;
    });

    this.authService.getCurrentUser().subscribe(user => {
      this.currentUser = user;
      this.isAuthenticated = !!user;
      
      // Only redirect if not on login/register pages and not authenticated
      const isAuthPage = this.currentUrl === '/login' || this.currentUrl === '/register';
      
      if (!this.isAuthenticated && !isAuthPage) {
        this.router.navigate(['/login']);
      }
    });
  }

  logout() {
    this.authService.logout();
    this.router.navigate(['/login']);
  }

  isAdmin(): boolean {
    return this.currentUser?.role === this.UserRole.ADMIN;
  }

  isSecurity(): boolean {
    return this.currentUser?.role === this.UserRole.SECURITY;
  }

  getUserDisplayName(): string {
    if (!this.currentUser) return '';
    return `${this.currentUser.profile.firstName} ${this.currentUser.profile.lastName}`;
  }
}
