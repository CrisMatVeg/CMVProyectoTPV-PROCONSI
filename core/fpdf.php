<?php
/*******************************************************************************
* FPDF consolidated for TPV - REPAIRED VERSION                                 *
*******************************************************************************/

if(!defined('FPDF_FONTPATH')) define('FPDF_FONTPATH', '');

class FPDF
{
    protected $page;
    protected $n;
    protected $offsets;
    protected $buffer;
    protected $pages;
    protected $state;
    protected $compress;
    protected $k;
    protected $DefOrientation;
    protected $CurOrientation;
    protected $StdPageSizes;
    protected $DefPageSize;
    protected $CurPageSize;
    protected $CurRotation;
    protected $PageLayout;
    protected $PageMode;
    protected $ZoomMode;
    protected $DisplayMode;
    protected $PageAnnots;
    protected $links;
    protected $AutoPageBreak;
    protected $PageBreakTrigger;
    protected $InHeader;
    protected $InFooter;
    protected $AliasNbPages;
    protected $CurFontSize;
    protected $fonts;
    protected $FontFiles;
    protected $encodings;
    protected $cmaps;
    protected $FontFamily;
    protected $FontStyle;
    protected $underline;
    protected $CurrentFont;
    protected $FontSizePt;
    protected $FontSize;
    protected $DrawColor;
    protected $FillColor;
    protected $TextColor;
    protected $ColorFlag;
    protected $ws;
    protected $images;
    protected $PageLinks;
    protected $diffs;
    protected $LineWidth;
    protected $extgstates;
    protected $metadata;
    protected $PDFVersion;
    protected $w, $h, $wPt, $hPt, $lMargin, $tMargin, $rMargin, $bMargin, $cMargin, $x, $y, $lasth;
    protected $CoreFonts;

    public function __construct($orientation='P', $unit='mm', $size='A4') {
        $this->page = 0; $this->n = 2; $this->buffer = ''; $this->pages = array();
        $this->PageAnnots = array(); $this->links = array(); $this->fonts = array();
        $this->FontFiles = array(); $this->encodings = array(); $this->cmaps = array();
        $this->diffs = array(); $this->images = array(); $this->PageLinks = array();
        $this->extgstates = array(); $this->InHeader = false; $this->InFooter = false;
        $this->ws = 0; $this->metadata = array(); $this->underline = false;
        $this->CoreFonts = array('courier', 'helvetica', 'times', 'symbol', 'zapfdingbats');
        if($unit=='pt') $this->k = 1; elseif($unit=='mm') $this->k = 72/25.4; elseif($unit=='cm') $this->k = 72/2.54; elseif($unit=='in') $this->k = 72; else $this->Error('Incorrect unit: '.$unit);
        $this->StdPageSizes = array('a3'=>array(841.89,1190.55), 'a4'=>array(595.28,841.89), 'a5'=>array(420.94,595.28), 'letter'=>array(612,792), 'legal'=>array(612,1008));
        $size = $this->_getpagesize($size);
        $this->DefPageSize = $size; $this->CurPageSize = $size;
        $orientation = strtolower($orientation);
        if($orientation=='p' || $orientation=='portrait') { $this->DefOrientation = 'P'; $this->w = $size[0]; $this->h = $size[1]; }
        elseif($orientation=='l' || $orientation=='landscape') { $this->DefOrientation = 'L'; $this->w = $size[1]; $this->h = $size[0]; }
        else $this->Error('Incorrect orientation: '.$orientation);
        $this->CurOrientation = $this->DefOrientation; $this->wPt = $this->w*$this->k; $this->hPt = $this->h*$this->k; $this->CurRotation = 0;
        $margin = 28.35/$this->k; $this->SetMargins($margin,$margin); $this->cMargin = $margin/10; $this->LineWidth = .567/$this->k;
        $this->SetAutoPageBreak(true,2*$margin); $this->SetDisplayMode('default'); $this->SetCompression(true); $this->PDFVersion = '1.3';
    }

    public function SetMargins($left, $top, $right=null) { $this->lMargin = $left; $this->tMargin = $top; if($right===null) $right = $left; $this->rMargin = $right; }
    public function SetLeftMargin($margin) { $this->lMargin = $margin; if($this->page>0 && $this->x<$margin) $this->x = $margin; }
    public function SetTopMargin($margin) { $this->tMargin = $margin; }
    public function SetRightMargin($margin) { $this->rMargin = $margin; }
    public function SetAutoPageBreak($auto, $margin=0) { $this->AutoPageBreak = $auto; $this->bMargin = $margin; $this->PageBreakTrigger = $this->h-$margin; }
    public function SetDisplayMode($zoom, $layout='default') { $this->ZoomMode = $zoom; $this->PageLayout = $layout; }
    public function SetCompression($compress) { $this->compress = (function_exists('gzcompress') && $compress); }
    public function SetTitle($title, $isUTF8=false) { $this->metadata['Title'] = $isUTF8 ? $title : mb_convert_encoding($title, 'UTF-8', 'ISO-8859-1'); }
    public function SetAuthor($author, $isUTF8=false) { $this->metadata['Author'] = $isUTF8 ? $author : mb_convert_encoding($author, 'UTF-8', 'ISO-8859-1'); }
    public function Error($msg) { throw new \Exception('FPDF error: '.$msg); }

    public function AddPage($orientation='', $size='', $rotation=0) {
        if($this->state==3) $this->Error('The document is closed');
        $family = $this->FontFamily; $style = $this->FontStyle.($this->underline ? 'U' : ''); $fontsize = $this->FontSizePt; $lw = $this->LineWidth; $dc = $this->DrawColor; $fc = $this->FillColor; $tc = $this->TextColor; $cf = $this->ColorFlag;
        if($this->page>0) { $this->InFooter = true; $this->Footer(); $this->InFooter = false; $this->_endpage(); }
        $this->_beginpage($orientation,$size,$rotation);
        $this->_out('2 J'); $this->LineWidth = $lw; $this->_out(sprintf('%.2F w',$lw*$this->k));
        if($family) $this->SetFont($family,$style,$fontsize);
        $this->DrawColor = $dc; if($dc!='') $this->_out($dc);
        $this->FillColor = $fc; if($fc!='') $this->_out($fc);
        $this->TextColor = $tc; $this->ColorFlag = $cf;
        $this->InHeader = true; $this->Header(); $this->InHeader = false;
        if($this->LineWidth!=$lw) { $this->LineWidth = $lw; $this->_out(sprintf('%.2F w',$lw*$this->k)); }
        if($family) $this->SetFont($family,$style,$fontsize);
    }
    public function Header() {} public function Footer() {}

    public function SetDrawColor($r, $g=null, $b=null) { if(($r==0 && $g==0 && $b==0) || $g===null) $this->DrawColor = sprintf('%.3F G',$r/255); else $this->DrawColor = sprintf('%.3F %.3F %.3F RG',$r/255,$g/255,$b/255); if($this->page>0) $this->_out($this->DrawColor); }
    public function SetFillColor($r, $g=null, $b=null) { if(($r==0 && $g==0 && $b==0) || $g===null) $this->FillColor = sprintf('%.3F g',$r/255); else $this->FillColor = sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255); $this->ColorFlag = ($this->FillColor!=$this->TextColor); if($this->page>0) $this->_out($this->FillColor); }
    public function SetTextColor($r, $g=null, $b=null) { if(($r==0 && $g==0 && $b==0) || $g===null) $this->TextColor = sprintf('%.3F g',$r/255); else $this->TextColor = sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255); $this->ColorFlag = ($this->FillColor!=$this->TextColor); }
    public function SetLineWidth($width) { $this->LineWidth = $width; if($this->page>0) $this->_out(sprintf('%.2F w',$width*$this->k)); }
    public function Line($x1, $y1, $x2, $y2) { $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S',$x1*$this->k,($this->h-$y1)*$this->k,$x2*$this->k,($this->h-$y2)*$this->k)); }
    public function SetX($x) { if($x>=0) $this->x = $x; else $this->x = $this->w+$x; }
    public function GetX() { return $this->x; }
    public function GetY() { return $this->y; }
    public function SetY($y, $resetX=true) { if($y>=0) $this->y = $y; else $this->y = $this->h+$y; if($resetX) $this->x = $this->lMargin; }
    public function SetXY($x, $y) { $this->SetY($y, false); $this->SetX($x); }
    
    public function SetFont($family, $style='', $size=0) {
        if($family=='') $family = $this->FontFamily; else $family = strtolower($family); $style = strtoupper($style);
        if(strpos($style,'U')!==false) { $this->underline = true; $style = str_replace('U','',$style); } else $this->underline = false;
        if($size==0) $size = $this->FontSizePt;
        $fontkey = $family.$style;
        if(!isset($this->fonts[$fontkey])) { if($family=='arial') $family = 'helvetica'; if(in_array($family,$this->CoreFonts)) { if($family=='symbol' || $family=='zapfdingbats') $style = ''; $fontkey = $family.$style; if(!isset($this->fonts[$fontkey])) $this->_loadfont($fontkey); } else $this->Error('Undefined font: '.$family.' '.$style); }
        $this->FontFamily = $family; $this->FontStyle = $style; $this->FontSizePt = $size; $this->FontSize = $size/$this->k; $this->CurrentFont = &$this->fonts[$fontkey]; if($this->page>0) $this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
    }

    public function GetStringWidth($s) { $s = (string)$s; $cw = &$this->CurrentFont['cw']; $w = 0; $l = strlen($s); for($i=0;$i<$l;$i++) { $c = $s[$i]; if(isset($cw[$c])) $w += $cw[$c]; else $w += 500; } return $w*$this->FontSize/1000; }

    public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        $k = $this->k;
        if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
            $x = $this->x; $this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation); $this->x = $x;
        }
        if($w==0) $w = $this->w-$this->rMargin-$this->x;
        $s = ''; if($fill || $border==1) { $op = ($fill ? ($border==1 ? 'B' : 'f') : 'S'); $s = sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op); }
        if(is_string($border)) { $x=$this->x; $y=$this->y; if(strpos($border,'L')!==false) $s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k); if(strpos($border,'T')!==false) $s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k); if(strpos($border,'R')!==false) $s.=sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k); if(strpos($border,'B')!==false) $s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k); }
        if($txt!=='') { if($align=='R') $dx=$w-$this->cMargin-$this->GetStringWidth($txt); elseif($align=='C') $dx=($w-$this->GetStringWidth($txt))/2; else $dx=$this->cMargin; if($this->ColorFlag) $s.='q '.$this->TextColor.' '; $s.=sprintf('BT %.2F %.2F Td (%s) Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$this->_escape($txt)); if($this->underline) $s.=' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt); if($this->ColorFlag) $s.=' Q'; }
        if($s) $this->_out($s); $this->lasth = $h; if($ln>0) { $this->y += $h; if($ln==1) $this->x = $this->lMargin; } else $this->x += $w;
    }

    public function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false) {
        $cw = &$this->CurrentFont['cw']; if($w==0) $w = $this->w-$this->rMargin-$this->x; $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize; $s = str_replace("\r", '', $txt); $nb = strlen($s); if($nb>0 && $s[$nb-1]=="\n") $nb--; $b = 0;
        if($border) { if($border==1) { $border='LTRB'; $b='LRT'; $b2='LR'; } else { $b2=''; if(strpos($border,'L')!==false) $b2.='L'; if(strpos($border,'R')!==false) $b2.='R'; $b=(strpos($border,'T')!==false ? $b2.'T' : $b2); } }
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while($i<$nb) { $c = $s[$i]; if($c=="\n") { $this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill); $i++; $sep = -1; $j = $i; $l = 0; $nl++; if($border && $nl==2) $b = $b2; continue; } if($c==' ') $sep = $i; $l += (isset($cw[$c]) ? $cw[$c] : 500); if($l>$wmax) { if($sep==-1) { if($i==$j) $i++; $this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill); } else { $this->Cell($w, $h, substr($s, $j, $sep-$j), $b, 2, $align, $fill); $i = $sep+1; } $sep = -1; $j = $i; $l = 0; $nl++; if($border && $nl==2) $b = $b2; } else $i++; }
        if($border && strpos($border, 'B')!==false) $b .= 'B'; $this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill); $this->x = $this->lMargin;
    }

    public function Ln($h=null) { $this->x = $this->lMargin; if($h===null) $this->y += $this->lasth; else $this->y += $h; }

    public function Output($dest='', $name='', $isUTF8=false) {
        $this->Close(); if($name=='') $name='doc.pdf';
        switch(strtoupper($dest)) {
            case 'I': header('Content-Type: application/pdf'); echo $this->buffer; break;
            case 'S': return $this->buffer;
            case 'F': file_put_contents($name,$this->buffer); break;
        }
        return '';
    }

    protected function _getpagesize($size) { 
        if(is_string($size)) { 
            $size = strtolower($size); 
            if(!isset($this->StdPageSizes[$size])) $this->Error('Unknown page size: '.$size);
            $a = $this->StdPageSizes[$size]; return array($a[0]/$this->k, $a[1]/$this->k); 
        } 
        if(is_array($size)) return $size; // SOPORTE PARA ARRAY(WIDTH, HEIGHT)
        return $size; 
    }
    protected function _beginpage($orientation, $size, $rotation) { $this->page++; $this->pages[$this->page] = ''; $this->state = 2; $this->x = $this->lMargin; $this->y = $this->tMargin; $this->FontFamily = ''; if($orientation=='') $orientation = $this->DefOrientation; else $orientation = strtoupper($orientation[0]); if($size=='') $size = $this->DefPageSize; else $size = $this->_getpagesize($size); if($orientation!=$this->CurOrientation || $size[0]!=$this->CurPageSize[0] || $size[1]!=$this->CurPageSize[1]) { if($orientation=='P') { $this->w = $size[0]; $this->h = $size[1]; } else { $this->w = $size[1]; $this->h = $size[0]; } $this->wPt = $this->w*$this->k; $this->hPt = $this->h*$this->k; $this->PageBreakTrigger = $this->h-$this->bMargin; $this->CurOrientation = $orientation; $this->CurPageSize = $size; } }
    protected function _endpage() { $this->state = 1; }
    protected function _loadfont($fontkey) { 
        $font = array(
           'helvetica'=>'Helvetica', 'helveticab'=>'Helvetica-Bold', 'helveticai'=>'Helvetica-Oblique', 'helveticabi'=>'Helvetica-BoldOblique',
           'courier'=>'Courier', 'courierb'=>'Courier-Bold', 'courieri'=>'Courier-Oblique', 'courierbi'=>'Courier-BoldOblique',
           'times'=>'Times-Roman', 'timesb'=>'Times-Bold', 'timesi'=>'Times-Italic', 'timesbi'=>'Times-BoldItalic'
        ); 
        $name = $font[strtolower($fontkey)]; 
        $cw = array(' '=>278,'!'=>278,'\"'=>355,'#'=>556,'$'=>556,'%'=>889,'&'=>667,'\''=>191,'('=>333,')'=>333,'*'=>389,'+'=>584,','=>278,'-'=>333,'.'=>278,'/'=>278,'0'=>556,'1'=>556,'2'=>556,'3'=>556,'4'=>556,'5'=>556,'6'=>556,'7'=>556,'8'=>556,'9'=>556,':'=>278,';'=>278,'<'=>584,'='=>584,'>'=>584,'?'=>556,'@'=>1015,'A'=>667,'B'=>667,'C'=>722,'D'=>722,'E'=>667,'F'=>611,'G'=>778,'H'=>722,'I'=>278,'J'=>500,'K'=>667,'L'=>556,'M'=>833,'N'=>722,'O'=>778,'P'=>667,'Q'=>778,'R'=>722,'S'=>667,'T'=>611,'U'=>722,'V'=>667,'W'=>944,'X'=>667,'Y'=>667,'Z'=>611,'['=>333,'\\'=>278,']'=>333,'^'=>469,'_'=>500,'`'=>333,'a'=>556,'b'=>556,'c'=>500,'d'=>556,'e'=>556,'f'=>278,'g'=>556,'h'=>556,'i'=>222,'j'=>222,'k'=>500,'l'=>222,'m'=>833,'n'=>556,'o'=>556,'p'=>556,'q'=>556,'r'=>333,'s'=>500,'t'=>278,'u'=>556,'v'=>500,'w'=>722,'x'=>500,'y'=>500,'z'=>500,'{'=>334,'|'=>278,'}'=>334,'~'=>584); 
        // Core fonts handle ISO-8859-1 (Win-1252)
        $cw[chr(128)] = 500; // Euro symbol
        $this->fonts[$fontkey] = array('i'=>count($this->fonts)+1, 'type'=>'core', 'name'=>$name, 'cw'=>$cw); 
    }
    protected function _escape($s) { return str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$s))); }
    protected function _out($s) { if($this->state==2) $this->pages[$this->page] .= $s."\n"; else $this->buffer .= $s."\n"; }
    public function Close() { if($this->state==3) return; if($this->page==0) $this->AddPage(); $this->InFooter = true; $this->Footer(); $this->InFooter = false; $this->_endpage(); $this->_enddoc(); }
    protected function _enddoc() { $this->_putheader(); $this->_putpages(); $this->_putresources(); $this->_newobj(); $this->_out('<< /Producer (FPDF) >>'); $this->_out('endobj'); $this->_newobj(); $this->_out('<< /Type /Catalog /Pages 1 0 R >>'); $this->_out('endobj'); $o = strlen($this->buffer); $this->_out('xref'); $this->_out('0 '.($this->n+1)); $this->_out('0000000000 65535 f '); for($i=1;$i<=$this->n;$i++) $this->_out(sprintf('%010d 00000 n ',$this->offsets[$i])); $this->_out('trailer'); $this->_out('<< /Size '.($this->n+1).' /Root '.$this->n.' 0 R /Info '.($this->n-1).' 0 R >>'); $this->_out('startxref'); $this->_out($o); $this->_out('%%EOF'); $this->state = 3; }
    protected function _putheader() { $this->_out('%PDF-'.$this->PDFVersion); $this->_out('%'.chr(226).chr(227).chr(207).chr(211)); }
    protected function _putpages() { $nb = $this->page; for($n=1;$n<=$nb;$n++) { $this->_newobj(); $this->_out('<< /Type /Page /Parent 1 0 R /Resources 2 0 R /Contents '.($this->n+1).' 0 R >>'); $this->_out('endobj'); $p = $this->pages[$n]; $this->_newobj(); $this->_out('<< /Length '.strlen($p).' >>'); $this->_out('stream'); $this->_out($p); $this->_out('endstream'); $this->_out('endobj'); } $this->offsets[1] = strlen($this->buffer); $this->_out('1 0 obj'); $this->_out('<< /Type /Pages /Kids ['); for($i=0;$i<$nb;$i++) $this->_out((3+2*$i).' 0 R'); $this->_out('] /Count '.$nb.' /MediaBox [0 0 '.$this->wPt.' '.$this->hPt.'] >>'); $this->_out('endobj'); }
    protected function _putresources() { $this->_putfonts(); $this->offsets[2] = strlen($this->buffer); $this->_out('2 0 obj'); $this->_out('<< /Font <<'); foreach($this->fonts as $font) $this->_out('/F'.$font['i'].' '.$font['n'].' 0 R'); $this->_out('>> >>'); $this->_out('endobj'); }
    protected function _putfonts() { 
    foreach($this->fonts as $k=>$font) { 
        $this->_newobj(); 
        $this->_out('<< /Type /Font /BaseFont /'.$font['name'].' /Subtype /Type1 /Encoding /WinAnsiEncoding >>'); 
        $this->_out('endobj'); 
        $this->fonts[$k]['n'] = $this->n; 
    } 
}
    protected function _newobj() { $this->n++; $this->offsets[$this->n] = strlen($this->buffer); $this->_out($this->n.' 0 obj'); }
    protected function _dounderline($x,$y,$txt) { $up=$this->CurrentFont['up']; $ut=$this->CurrentFont['ut']; $w=$this->GetStringWidth($txt)+$this->ws*substr_count($txt,' '); $y=$this->h-($y-$up/1000*$this->FontSize); return sprintf('%.2F %.2F %.2F %.2F re f',$x*$this->k,$y*$this->k,$w*$this->k,-$ut/1000*$this->FontSizePt); }
    protected function AcceptPageBreak() { return $this->AutoPageBreak; }
}
