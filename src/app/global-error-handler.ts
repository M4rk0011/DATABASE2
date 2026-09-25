// src/app/global-error-handler.ts
import { ErrorHandler } from '@angular/core';

export class GlobalErrorHandler implements ErrorHandler {
  handleError(error: any): void {
    console.error('Global Error: ', error); // Log the error to the console

    // Optionally, display a user-friendly message
    alert('An unexpected error occurred. Please check the console for more details.');
  }
}