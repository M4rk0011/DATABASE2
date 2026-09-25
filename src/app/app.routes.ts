import { Routes } from '@angular/router';
import { AuthGuard } from './guards/auth.guard';

export const routes: Routes = [
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  { path: 'login', loadComponent: () => import('./comps/auth/login/login.component').then(m => m.LoginComponent) },
  { path: 'register', loadComponent: () => import('./comps/auth/register/register.component').then(m => m.RegisterComponent) },
  { path: 'dashboard', loadComponent: () => import('./comps/dashboard/dashboard.component').then(m => m.DashboardComponent), canActivate: [AuthGuard] },
  { path: 'map', loadComponent: () => import('./comps/incident-map/incident-map.component').then(m => m.IncidentMapComponent), canActivate: [AuthGuard] },
  { path: 'report', loadComponent: () => import('./comps/report-incident/report-incident.component').then(m => m.ReportIncidentComponent), canActivate: [AuthGuard] },
  { path: 'incidents', loadComponent: () => import('./comps/incident-list/incident-list.component').then(m => m.IncidentListComponent), canActivate: [AuthGuard] },
  { path: 'incidents/:id', loadComponent: () => import('./comps/incident-details/incident-details.component').then(m => m.IncidentDetailsComponent), canActivate: [AuthGuard] },
  { path: '**', redirectTo: 'login' }
];
