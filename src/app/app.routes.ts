import { Routes } from '@angular/router';
import { AuthGuard } from './guards/auth.guard';

export const routes: Routes = [
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  { path: 'login', loadComponent: () => import('./comps/auth/login/login.component').then(m => m.LoginComponent) },
  { path: 'register', loadComponent: () => import('./comps/auth/register/register.component').then(m => m.RegisterComponent) },
  { path: 'dashboard', loadComponent: () => import('./comps/dashboard/dashboard.component').then(m => m.DashboardComponent), canActivate: [AuthGuard] },
  { path: 'map', loadComponent: () => import('./comps/concern-map/concern-map.component').then(m => m.ConcernMapComponent), canActivate: [AuthGuard] },
  { path: 'report', loadComponent: () => import('./comps/report-concern/report-concern.component').then(m => m.ReportConcernComponent), canActivate: [AuthGuard] },
  { path: 'concerns', loadComponent: () => import('./comps/concern-list/concern-list.component').then(m => m.ConcernListComponent), canActivate: [AuthGuard] },
  { path: 'concerns/:id', loadComponent: () => import('./comps/concern-details/concern-details.component').then(m => m.ConcernDetailsComponent), canActivate: [AuthGuard] },
  { path: '**', redirectTo: 'login' }
];
