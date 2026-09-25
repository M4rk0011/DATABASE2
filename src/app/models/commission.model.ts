export interface Commission {
  id: string;
  title: string;
  description: string;
  clientId: string;
  clientName: string;
  clientEmail: string;
  status: CommissionStatus;
  priority: CommissionPriority;
  price: number;
  deadline: Date;
  createdAt: Date;
  updatedAt: Date;
  progress: number; // 0-100
  artworkType: ArtworkType;
  specifications: string;
  referenceImages: string[];
  milestones: Milestone[];
  payments: Payment[];
}

export interface Client {
  id: string;
  name: string;
  email: string;
  phone?: string;
  company?: string;
  address?: string;
  createdAt: Date;
  totalCommissions: number;
  totalSpent: number;
  notes?: string;
}

export interface Milestone {
  id: string;
  title: string;
  description: string;
  completed: boolean;
  completedAt?: Date;
  dueDate: Date;
  commissionId: string;
}

export interface Payment {
  id: string;
  amount: number;
  dueDate: Date;
  paidDate?: Date;
  status: PaymentStatus;
  commissionId: string;
  type: PaymentType;
}

export enum CommissionStatus {
  REQUESTED = 'requested',
  ACCEPTED = 'accepted',
  IN_PROGRESS = 'in_progress',
  REVIEW = 'review',
  COMPLETED = 'completed',
  CANCELLED = 'cancelled'
}

export enum CommissionPriority {
  LOW = 'low',
  MEDIUM = 'medium',
  HIGH = 'high',
  URGENT = 'urgent'
}

export enum ArtworkType {
  DIGITAL_ART = 'digital_art',
  TRADITIONAL_ART = 'traditional_art',
  ILLUSTRATION = 'illustration',
  CHARACTER_DESIGN = 'character_design',
  LOGO_DESIGN = 'logo_design',
  PORTRAIT = 'portrait',
  CONCEPT_ART = 'concept_art',
  COMMISSION = 'commission'
}

export enum PaymentStatus {
  PENDING = 'pending',
  PAID = 'paid',
  OVERDUE = 'overdue'
}

export enum PaymentType {
  DEPOSIT = 'deposit',
  MILESTONE = 'milestone',
  FINAL = 'final'
}
