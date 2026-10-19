<?php
require __DIR__.'/../app/bootstrap.php';
$currentUser=isset($_SESSION['user_id'])?findUser((string)$_SESSION['user_id']):null;
if(!$currentUser){header('Location: index.php?page=login');exit;}
$id=is_string($_GET['id']??null)?$_GET['id']:'';$r=findReport($id);
if(!$r){http_response_code(404);exit('Concern report not found.');}
require __DIR__.'/../app/vendor/fpdf/fpdf.php';
function pdfText(string $s): string {return iconv('UTF-8','windows-1252//TRANSLIT//IGNORE',$s) ?: '';}
$pdf=new FPDF();$pdf->SetTitle('Concern Report '.$r->id);$pdf->SetAuthor('University of Baguio');$pdf->SetMargins(18,18,18);$pdf->SetAutoPageBreak(true,18);$pdf->AddPage();
$pdf->SetFont('Helvetica','B',18);$pdf->SetTextColor(170,30,50);$pdf->MultiCell(0,10,'University of Baguio');$pdf->SetFont('Helvetica','B',14);$pdf->MultiCell(0,8,'Concern Report System');$pdf->Ln(5);$pdf->SetTextColor(30,30,30);
$pdf->SetFont('Helvetica','B',16);$pdf->MultiCell(0,8,pdfText($r->title));$pdf->Ln(4);
$fields=['Report ID'=>$r->id,'Category'=>getCategoryDisplayName($r->category),'Severity'=>strtoupper($r->severity),'Status'=>getCategoryDisplayName($r->status),'Reported'=>fmtdate($r->reportedAt),'Updated'=>fmtdate($r->updatedAt),'Assigned to'=>$r->assignedTo?:'Unassigned','Description'=>$r->description,'Location'=>$r->location->address,'Building'=>$r->location->building,'Floor'=>$r->location->floor,'Room / area'=>$r->location->room,'Landmark'=>$r->location->landmark,'Coordinates'=>$r->location->latitude.', '.$r->location->longitude,'Reporter'=>$r->isAnonymous?'Anonymous':$r->contactInfo->name];
if(!$r->isAnonymous||isAdmin()){foreach(['email'=>'Email','phone'=>'Phone','studentId'=>'Student ID','department'=>'Department'] as $key=>$label)$fields[$label]=$r->contactInfo->$key;}
$fields['Witnesses']=implode(', ',$r->witnesses);$fields['Additional notes']=$r->additionalNotes;
foreach($fields as $label=>$value){if($value==='')continue;$pdf->SetFont('Helvetica','B',10);$pdf->MultiCell(0,6,$label);$pdf->SetFont('Helvetica','',11);$pdf->MultiCell(0,6,pdfText((string)$value));$pdf->Ln(3);}
$pdf->SetFont('Helvetica','I',9);$pdf->MultiCell(0,6,'Generated '.date('Y-m-d H:i').' (Asia/Manila)');
$pdf->Output('D','Concern-Report-'.preg_replace('/[^a-zA-Z0-9_-]/','',$r->id).'.pdf');
