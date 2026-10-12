import { Injectable } from '@angular/core';
import { Concern } from '../models/concern.model';

@Injectable({
  providedIn: 'root'
})
export class PdfService {

  generateConcernPDF(concern: Concern): void {
    try {
      const printContent = this.createConcernPrintContent(concern);
      const printWindow = window.open('', '_blank', 'width=800,height=600');

      if (printWindow) {
        printWindow.document.write(printContent);
        printWindow.document.close();

        // Wait for content to load, then print
        printWindow.onload = () => {
          setTimeout(() => {
            printWindow.print();
            // Don't close immediately, let user handle print dialog
            setTimeout(() => {
              printWindow.close();
            }, 1000);
          }, 500);
        };
      } else {
        console.error('Failed to open print window. Please allow popups for this site.');
        alert('Please allow popups for this site to print concern reports.');
      }
    } catch (error) {
      console.error('Error generating PDF:', error);
      alert('Failed to generate PDF. Please try again.');
    }
  }

  downloadConcernPDF(concern: Concern): void {
    console.log('Opening concern report in new window for:', concern.id);

    // Simple window approach - always works
    try {
      const printContent = this.createConcernPrintContent(concern);
      const newWindow = window.open('', '_blank', 'width=900,height=700,scrollbars=yes,resizable=yes');

      if (newWindow) {
        newWindow.document.write(printContent);
        newWindow.document.close();

        // Add download instructions
        setTimeout(() => {
          const instructions = newWindow.document.createElement('div');
          instructions.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px;
            border-radius: 8px;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            font-family: Arial, sans-serif;
            max-width: 300px;
          `;
          instructions.innerHTML = `
            <h4 style="margin: 0 0 10px 0; font-size: 16px;">📄 Save This Report</h4>
            <p style="margin: 0 0 10px 0; font-size: 14px;">Press <strong>Ctrl+S</strong> or right-click and select "Save As..." to download this concern report.</p>
            <button onclick="window.close()" style="
              background: white;
              color: #007bff;
              border: none;
              padding: 8px 15px;
              border-radius: 5px;
              cursor: pointer;
              font-weight: bold;
              margin-right: 5px;
            ">Close</button>
            <button onclick="window.print()" style="
              background: #28a745;
              color: white;
              border: none;
              padding: 8px 15px;
              border-radius: 5px;
              cursor: pointer;
              font-weight: bold;
            ">Print</button>
          `;
          newWindow.document.body.appendChild(instructions);
        }, 500);

        console.log('Concern report opened in new window');
        alert('Concern report opened in new window! Use Ctrl+S to save or Print button to print.');

      } else {
        alert('Please allow popups for this site to view concern reports.');
      }
    } catch (error) {
      console.error('Error opening concern report:', error);
      alert('Unable to open concern report. Please check your popup settings.');
    }
  }

  private createConcernPrintContent(concern: Concern): string {
    const currentDate = new Date().toLocaleDateString();
    const severityColor = this.getSeverityColor(concern.severity);
    const statusColor = this.getStatusColor(concern.status);

    return `
<!DOCTYPE html>
<html>
<head>
    <title>Concern Report - ${concern.id}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #dc3545;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #dc3545;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        .concern-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
        }
        .info-section h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }
        .info-item {
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        .severity-badge {
            background: ${severityColor};
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-badge {
            background: ${statusColor};
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .description-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .description-section h3 {
            margin: 0 0 15px 0;
            color: #333;
        }
        .description-text {
            line-height: 1.6;
            color: #333;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        @media print {
            body { margin: 0; padding: 15px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>UNIVERSITY OF BAGUIO</h1>
        <p>Concern Report System</p>
        <p><strong>Concern Report #${concern.id}</strong></p>
    </div>

    <div class="concern-info">
        <div class="info-section">
            <h3>Concern Details</h3>
            <div class="info-item">
                <span class="info-label">Title:</span>
                <span class="info-value">${concern.title}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Severity:</span>
                <span class="info-value">
                    <span class="severity-badge">${concern.severity.toUpperCase()}</span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="status-badge">${concern.status.replace('_', ' ').toUpperCase()}</span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Reported Date:</span>
                <span class="info-value">${new Date(concern.reportedAt).toLocaleDateString()}</span>
            </div>
        </div>

        <div class="info-section">
            <h3>Location Information</h3>
            <div class="info-item">
                <span class="info-label">Address:</span>
                <span class="info-value">${concern.location.address}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Coordinates:</span>
                <span class="info-value">${concern.location.latitude.toFixed(6)}, ${concern.location.longitude.toFixed(6)}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Building/Area:</span>
                <span class="info-value">${concern.location.building || 'N/A'}</span>
            </div>
        </div>
    </div>

    <div class="description-section">
        <h3>Concern Description</h3>
        <div class="description-text">
            ${concern.description}
        </div>
    </div>

    <div class="concern-info">
        <div class="info-section">
            <h3>Reporter Information</h3>
            <div class="info-item">
                <span class="info-label">Name:</span>
                <span class="info-value">${concern.contactInfo.name}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value">${concern.contactInfo.email}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Phone:</span>
                <span class="info-value">${concern.contactInfo.phone || 'N/A'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Department:</span>
                <span class="info-value">${concern.contactInfo.department || 'N/A'}</span>
            </div>
        </div>

        <div class="info-section">
            <h3>Assigned To</h3>
            <div class="info-item">
                <span class="info-label">Staff:</span>
                <span class="info-value">${concern.assignedTo || 'Unassigned'}</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Generated on ${currentDate} | University of Baguio Concern Report System</p>
        <p>This is an official concern report document</p>
    </div>
</body>
</html>`;
  }

  private getSeverityColor(severity: string): string {
    const colors: Record<string, string> = {
      'critical': '#dc3545',
      'high': '#fd7e14',
      'medium': '#ffc107',
      'low': '#28a745'
    };
    return colors[severity] || '#6c757d';
  }

  private getStatusColor(status: string): string {
    const colors: Record<string, string> = {
      'reported': '#007bff',
      'investigating': '#fd7e14',
      'resolved': '#28a745',
      'closed': '#6c757d',
      'false_alarm': '#6f42c1'
    };
    return colors[status] || '#6c757d';
  }

  private openConcernInWindow(concern: Concern): void {
    try {
      const printContent = this.createConcernPrintContent(concern);
      const newWindow = window.open('', '_blank', 'width=800,height=600');

      if (newWindow) {
        newWindow.document.write(printContent);
        newWindow.document.close();

        // Add download instructions
        setTimeout(() => {
          const instructions = document.createElement('div');
          instructions.innerHTML = `
            <div style="position: fixed; top: 10px; right: 10px; background: #007bff; color: white; padding: 10px; border-radius: 5px; z-index: 9999;">
              <p>Right-click and select "Save As..." to download this report</p>
              <button onclick="window.close()" style="background: white; color: #007bff; border: none; padding: 5px; border-radius: 3px; cursor: pointer;">Close</button>
            </div>
          `;
          newWindow.document.body.appendChild(instructions);
        }, 1000);

        console.log('Opened concern in new window for manual download');
      }
    } catch (error) {
      console.error('Window approach also failed:', error);
      alert('Unable to generate PDF. Please use the Print function instead.');
    }
  }

  shareConcernReport(concern: Concern): void {
    try {
      const reportContent = this.createConcernPrintContent(concern);
      const subject = `Concern Report #${concern.id} - ${concern.title}`;
      const body = `Please find the concern report attached or view it below:\n\nTitle: ${concern.title}\nSeverity: ${concern.severity}\nStatus: ${concern.status}\nLocation: ${concern.location.address}\n\nFull report details are available in the attachment.`;

      // Create mailto link
      const mailtoLink = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;

      window.open(mailtoLink, '_blank');
      console.log('Email client opened for sharing concern report');
    } catch (error) {
      console.error('Error sharing concern report:', error);
      alert('Failed to open email client. Please try again.');
    }
  }
}
