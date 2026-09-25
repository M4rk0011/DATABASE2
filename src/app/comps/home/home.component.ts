import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CommissionService } from '../../services/commission.service';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent implements OnInit {
  stats: any = {};
  recentCommissions: any[] = [];
  
  constructor(private commissionService: CommissionService) { }

  ngOnInit() {
    this.stats = this.commissionService.getCommissionStats();
    this.commissionService.getCommissions().subscribe(commissions => {
      this.recentCommissions = commissions
        .sort((a, b) => b.updatedAt.getTime() - a.updatedAt.getTime())
        .slice(0, 5);
    });
  }
}
