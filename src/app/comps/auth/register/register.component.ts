import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../services/auth.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css']
})
export class RegisterComponent {
  registerData = {
    username: '',
    email: '',
    password: '',
    confirmPassword: '',
    firstName: '',
    lastName: '',
    phone: '',
    studentId: '',
    department: ''
  };
  isLoading = false;
  errorMessage = '';
  successMessage = '';
  showPassword = false;
  showConfirmPassword = false;

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  onRegister() {
    // Reset messages
    this.errorMessage = '';
    this.successMessage = '';

    // Validation
    if (!this.validateForm()) {
      return;
    }

    this.isLoading = true;

    this.authService.register({
      username: this.registerData.username,
      email: this.registerData.email,
      password: this.registerData.password,
      firstName: this.registerData.firstName,
      lastName: this.registerData.lastName,
      phone: this.registerData.phone,
      studentId: this.registerData.studentId,
      department: this.registerData.department
    }).subscribe(
      (success) => {
        this.isLoading = false;
        if (success) {
          this.successMessage = 'Registration successful! Please login with your credentials.';
          setTimeout(() => {
            this.router.navigate(['/login']);
          }, 2000);
        } else {
          this.errorMessage = 'Username or email already exists';
        }
      },
      (error) => {
        this.isLoading = false;
        this.errorMessage = 'Registration failed. Please try again.';
      }
    );
  }

  private validateForm(): boolean {
    if (!this.registerData.username || !this.registerData.email || 
        !this.registerData.password || !this.registerData.firstName || 
        !this.registerData.lastName) {
      this.errorMessage = 'Please fill in all required fields';
      return false;
    }

    if (this.registerData.password !== this.registerData.confirmPassword) {
      this.errorMessage = 'Passwords do not match';
      return false;
    }

    if (this.registerData.password.length < 6) {
      this.errorMessage = 'Password must be at least 6 characters long';
      return false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(this.registerData.email)) {
      this.errorMessage = 'Please enter a valid email address';
      return false;
    }

    return true;
  }

  togglePassword() {
    this.showPassword = !this.showPassword;
  }

  toggleConfirmPassword() {
    this.showConfirmPassword = !this.showConfirmPassword;
  }

  goToLogin() {
    this.router.navigate(['/login']);
  }
}
