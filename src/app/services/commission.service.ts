import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { Commission, CommissionStatus, CommissionPriority, ArtworkType, Client, Payment, PaymentStatus, PaymentType, Milestone } from '../models/commission.model';

@Injectable({
  providedIn: 'root'
})
export class CommissionService {
  private commissions: Commission[] = [];
  private commissionsSubject = new BehaviorSubject<Commission[]>([]);
  
  private clients: Client[] = [];
  private clientsSubject = new BehaviorSubject<Client[]>([]);

  constructor() {
    this.initializeMockData();
  }

  private initializeMockData() {
    // Mock clients
    this.clients = [
      {
        id: '1',
        name: 'Sarah Johnson',
        email: 'sarah.j@email.com',
        phone: '+1-555-0123',
        createdAt: new Date('2024-01-15'),
        totalCommissions: 3,
        totalSpent: 1500,
        notes: 'Prefers digital art, quick responses'
      },
      {
        id: '2',
        name: 'Mike Tech Solutions',
        email: 'contact@miketech.com',
        company: 'Mike Tech Solutions',
        createdAt: new Date('2024-02-01'),
        totalCommissions: 5,
        totalSpent: 3500,
        notes: 'Corporate client, needs invoices'
      }
    ];

    // Mock commissions
    this.commissions = [
      {
        id: '1',
        title: 'Character Portrait Commission',
        description: 'Full body character portrait with fantasy theme',
        clientId: '1',
        clientName: 'Sarah Johnson',
        clientEmail: 'sarah.j@email.com',
        status: CommissionStatus.IN_PROGRESS,
        priority: CommissionPriority.MEDIUM,
        price: 500,
        deadline: new Date('2024-05-15'),
        createdAt: new Date('2024-04-01'),
        updatedAt: new Date('2024-04-10'),
        progress: 65,
        artworkType: ArtworkType.PORTRAIT,
        specifications: 'Character: Female elf warrior, armor design, forest background',
        referenceImages: [],
        milestones: [
          {
            id: '1',
            title: 'Sketch Approval',
            description: 'Initial sketch for client review',
            completed: true,
            completedAt: new Date('2024-04-05'),
            dueDate: new Date('2024-04-07'),
            commissionId: '1'
          },
          {
            id: '2',
            title: 'Line Art',
            description: 'Clean line art completion',
            completed: true,
            completedAt: new Date('2024-04-10'),
            dueDate: new Date('2024-04-12'),
            commissionId: '1'
          },
          {
            id: '3',
            title: 'Coloring',
            description: 'Full color application',
            completed: false,
            dueDate: new Date('2024-04-20'),
            commissionId: '1'
          }
        ],
        payments: [
          {
            id: '1',
            amount: 150,
            dueDate: new Date('2024-04-01'),
            paidDate: new Date('2024-04-02'),
            status: PaymentStatus.PAID,
            commissionId: '1',
            type: PaymentType.DEPOSIT
          },
          {
            id: '2',
            amount: 200,
            dueDate: new Date('2024-04-15'),
            paidDate: undefined,
            status: PaymentStatus.PENDING,
            commissionId: '1',
            type: PaymentType.MILESTONE
          },
          {
            id: '3',
            amount: 150,
            dueDate: new Date('2024-05-15'),
            paidDate: undefined,
            status: PaymentStatus.PENDING,
            commissionId: '1',
            type: PaymentType.FINAL
          }
        ]
      },
      {
        id: '2',
        title: 'Company Logo Design',
        description: 'Modern logo design for tech startup',
        clientId: '2',
        clientName: 'Mike Tech Solutions',
        clientEmail: 'contact@miketech.com',
        status: CommissionStatus.REQUESTED,
        priority: CommissionPriority.HIGH,
        price: 800,
        deadline: new Date('2024-05-01'),
        createdAt: new Date('2024-04-10'),
        updatedAt: new Date('2024-04-10'),
        progress: 0,
        artworkType: ArtworkType.LOGO_DESIGN,
        specifications: 'Tech company, blue color scheme, minimalist design',
        referenceImages: [],
        milestones: [
          {
            id: '1',
            title: 'Concept Sketches',
            description: '3 different logo concepts',
            completed: false,
            dueDate: new Date('2024-04-20'),
            commissionId: '2'
          }
        ],
        payments: [
          {
            id: '1',
            amount: 240,
            dueDate: new Date('2024-04-15'),
            paidDate: undefined,
            status: PaymentStatus.PENDING,
            commissionId: '2',
            type: PaymentType.DEPOSIT
          }
        ]
      }
    ];

    this.commissionsSubject.next(this.commissions);
    this.clientsSubject.next(this.clients);
  }

  // Commission methods
  getCommissions(): Observable<Commission[]> {
    return this.commissionsSubject.asObservable();
  }

  getCommissionById(id: string): Observable<Commission | undefined> {
    const commission = this.commissions.find(c => c.id === id);
    return of(commission);
  }

  createCommission(commission: Omit<Commission, 'id' | 'createdAt' | 'updatedAt'>): void {
    const newCommission: Commission = {
      ...commission,
      id: (this.commissions.length + 1).toString(),
      createdAt: new Date(),
      updatedAt: new Date()
    };
    this.commissions.push(newCommission);
    this.commissionsSubject.next(this.commissions);
  }

  updateCommission(id: string, updates: Partial<Commission>): void {
    const index = this.commissions.findIndex(c => c.id === id);
    if (index !== -1) {
      this.commissions[index] = {
        ...this.commissions[index],
        ...updates,
        updatedAt: new Date()
      };
      this.commissionsSubject.next(this.commissions);
    }
  }

  deleteCommission(id: string): void {
    this.commissions = this.commissions.filter(c => c.id !== id);
    this.commissionsSubject.next(this.commissions);
  }

  // Client methods
  getClients(): Observable<Client[]> {
    return this.clientsSubject.asObservable();
  }

  getClientById(id: string): Observable<Client | undefined> {
    const client = this.clients.find(c => c.id === id);
    return of(client);
  }

  createClient(client: Omit<Client, 'id' | 'createdAt' | 'totalCommissions' | 'totalSpent'>): void {
    const newClient: Client = {
      ...client,
      id: (this.clients.length + 1).toString(),
      createdAt: new Date(),
      totalCommissions: 0,
      totalSpent: 0
    };
    this.clients.push(newClient);
    this.clientsSubject.next(this.clients);
  }

  // Statistics methods
  getCommissionStats() {
    const total = this.commissions.length;
    const completed = this.commissions.filter(c => c.status === CommissionStatus.COMPLETED).length;
    const inProgress = this.commissions.filter(c => c.status === CommissionStatus.IN_PROGRESS).length;
    const totalRevenue = this.commissions.reduce((sum, c) => sum + c.price, 0);
    const pendingRevenue = this.commissions
      .filter(c => c.status !== CommissionStatus.COMPLETED)
      .reduce((sum, c) => sum + c.price, 0);

    return {
      total,
      completed,
      inProgress,
      totalRevenue,
      pendingRevenue,
      completionRate: total > 0 ? (completed / total) * 100 : 0
    };
  }
}
