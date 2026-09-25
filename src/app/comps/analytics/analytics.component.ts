import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CommissionService } from '../../services/commission.service';
import { Commission, CommissionStatus, ArtworkType } from '../../models/commission.model';

@Component({
  selector: 'app-analytics',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './analytics.component.html',
  styleUrls: ['./analytics.component.css']
})
export class AnalyticsComponent implements OnInit {
  stats: any = {};
  commissions: Commission[] = [];
  statusData: Record<string, number> = {};
  artworkTypeData: Record<string, number> = {};
  monthlyData: { month: string; revenue: number; commissions: number }[] = [];

  constructor(private commissionService: CommissionService) {}

  ngOnInit() {
    this.loadAnalyticsData();
  }

  loadAnalyticsData() {
    this.stats = this.commissionService.getCommissionStats();
    this.commissionService.getCommissions().subscribe(commissions => {
      this.commissions = commissions;
      this.processCommissionData();
    });
  }

  processCommissionData() {
    // Process status distribution
    this.statusData = this.commissions.reduce((acc: Record<string, number>, commission) => {
      const status = commission.status.replace('_', ' ').toUpperCase();
      acc[status] = (acc[status] || 0) + 1;
      return acc;
    }, {});

    // Process artwork type distribution
    this.artworkTypeData = this.commissions.reduce((acc: Record<string, number>, commission) => {
      const type = commission.artworkType.replace('_', ' ').toUpperCase();
      acc[type] = (acc[type] || 0) + 1;
      return acc;
    }, {});

    // Process monthly data (last 6 months)
    this.monthlyData = this.generateMonthlyData();
  }

  generateMonthlyData() {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const currentMonth = new Date().getMonth();
    const data = [];

    for (let i = 5; i >= 0; i--) {
      const monthIndex = (currentMonth - i + 12) % 12;
      const monthName = months[monthIndex];
      
      const monthCommissions = this.commissions.filter(c => {
        const commissionMonth = new Date(c.createdAt).getMonth();
        return commissionMonth === monthIndex;
      });

      data.push({
        month: monthName,
        revenue: monthCommissions.reduce((sum, c) => sum + c.price, 0),
        commissions: monthCommissions.length
      });
    }

    return data;
  }

  getAverageCommissionValue(): number {
    return this.commissions.length > 0 
      ? this.commissions.reduce((sum, c) => sum + c.price, 0) / this.commissions.length 
      : 0;
  }

  getCompletionRate(): number {
    const completed = this.commissions.filter(c => c.status === CommissionStatus.COMPLETED).length;
    return this.commissions.length > 0 ? (completed / this.commissions.length) * 100 : 0;
  }

  getAverageProgress(): number {
    return this.commissions.length > 0 
      ? this.commissions.reduce((sum, c) => sum + c.progress, 0) / this.commissions.length 
      : 0;
  }
}
