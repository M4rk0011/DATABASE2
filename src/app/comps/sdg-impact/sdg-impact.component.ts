import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CommissionService } from '../../services/commission.service';

@Component({
  selector: 'app-sdg-impact',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './sdg-impact.component.html',
  styleUrls: ['./sdg-impact.component.css']
})
export class SdgImpactComponent implements OnInit {
  stats: any = {};
  impactMetrics: any = {};

  constructor(private commissionService: CommissionService) {}

  ngOnInit() {
    this.loadImpactData();
  }

  loadImpactData() {
    this.stats = this.commissionService.getCommissionStats();
    this.calculateImpactMetrics();
  }

  calculateImpactMetrics() {
    // Economic impact calculations
    this.impactMetrics = {
      totalRevenue: this.stats.totalRevenue,
      jobsSupported: Math.ceil(this.stats.totalRevenue / 30000), // Estimated jobs supported
      averageIncome: this.stats.total > 0 ? this.stats.totalRevenue / this.stats.total : 0,
      productivityGain: this.stats.completed * 8, // Hours saved through efficient management
      entrepreneurshipSupport: this.stats.total, // Number of entrepreneurial activities supported
      sustainableIncome: this.stats.completed > 0 ? (this.stats.totalRevenue / this.stats.completed) : 0,
      economicGrowthContribution: this.stats.totalRevenue * 0.15 // Estimated contribution to local economy
    };
  }

  getImpactLevel(metric: number, threshold: number): string {
    if (metric >= threshold) return 'high';
    if (metric >= threshold * 0.6) return 'medium';
    return 'low';
  }

  getImpactColor(level: string): string {
    switch (level) {
      case 'high': return '#28a745';
      case 'medium': return '#ffc107';
      case 'low': return '#dc3545';
      default: return '#6c757d';
    }
  }

  getSdgTargets(): { target: string; description: string; achieved: boolean }[] {
    return [
      {
        target: '8.1',
        description: 'Sustain economic growth per capita',
        achieved: this.impactMetrics.totalRevenue > 1000
      },
      {
        target: '8.2',
        description: 'Achieve higher levels of economic productivity',
        achieved: this.impactMetrics.productivityGain > 20
      },
      {
        target: '8.3',
        description: 'Promote development-oriented policies that support productive activities',
        achieved: this.impactMetrics.entrepreneurshipSupport > 0
      },
      {
        target: '8.6',
        description: 'Substantially reduce the proportion of youth not in employment',
        achieved: this.impactMetrics.jobsSupported > 0
      },
      {
        target: '8.10',
        description: 'Strengthen the capacity of domestic financial institutions',
        achieved: this.impactMetrics.averageIncome > 100
      }
    ];
  }
}
