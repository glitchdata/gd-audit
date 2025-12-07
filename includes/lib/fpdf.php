<?php
/*************************************************************************
*                           FPDF                                          *
*                                                                         *
*   Version: 1.85                                                         *
*   Date:    2022-01-17                                                   *
*   Author:  Olivier PLATHEY                                              *
*   License: Freeware                                                     *
*   Web:     http://www.fpdf.org/                                         *
*                                                                         *
*************************************************************************/

if (class_exists('FPDF', false)) {
    return;
}

class FPDF
{
    protected $page;               // current page number
    protected $n;                  // current object number
    protected $offsets;            // array of object offsets
    protected $buffer;             // buffer holding in-memory PDF
    protected $pages;              // array containing pages
    protected $state;              // current document state
    protected $compress;           // compression flag
    protected $k;                  // scale factor (number of points in user unit)
    protected $DefOrientation;     // default orientation
    protected $CurOrientation;     // current orientation
    protected $StdPageSizes;       // standard page sizes
    protected $DefPageSize;        // default page size
    protected $CurPageSize;        // current page size
    protected $PageSizes;          // used for pages with non default sizes or orientations
    protected $wPt, $hPt;          // dimensions of current page in points
    protected $w, $h;              // dimensions of current page in user unit
    protected $lMargin;            // left margin
    protected $tMargin;            // top margin
    protected $rMargin;            // right margin
    protected $bMargin;            // page break margin
    protected $cMargin;            // cell margin
    protected $x, $y;              // current position in user unit
    protected $lasth;              // height of last printed cell
    protected $LineWidth;          // line width in user unit
    protected $fontpath;           // path containing fonts
    protected $CoreFonts;          // array of core font names
    protected $fonts;              // array of used fonts
    protected $FontFiles;          // array of font files
    protected $encodings;          // array of encodings
    protected $cmaps;              // array of ToUnicode cmaps
    protected $FontFamily;         // current font family
    protected $FontStyle;          // current font style
    protected $underline;          // underlining flag
    protected $CurrentFont;        // current font info
    protected $FontSizePt;         // current font size in points
    protected $FontSize;           // current font size in user unit
    protected $DrawColor;          // commands for drawing color
    protected $FillColor;          // commands for filling color
    protected $TextColor;          // commands for text color
    protected $ColorFlag;          // indicates whether fill and text colors are different
    protected $WithAlpha;          // indicates whether alpha channel is used
    protected $ws;                 // word spacing
    protected $images;             // array of used images
    protected $PageLinks;          // array of links in pages
    protected $links;              // array of internal links
    protected $AutoPageBreak;      // automatic page breaking
    protected $PageBreakTrigger;   // threshold used to trigger page breaks
    protected $InHeader;           // flag set when processing header
    protected $InFooter;           // flag set when processing footer
    protected $ZoomMode;           // zoom display mode
    protected $LayoutMode;         // layout display mode
    protected $title;              // title
    protected $subject;            // subject
    protected $author;             // author
    protected $keywords;           // keywords
    protected $creator;            // creator
    protected $AliasNbPages;       // alias for total number of pages
    protected $PDFVersion;         // PDF version number

    function __construct($orientation='P', $unit='mm', $size='A4')
    {
        $this->page = 0;
        $this->n = 0;
        $this->buffer = '';
        $this->pages = [];
        $this->PageSizes = [];
        $this->state = 0;
        $this->fonts = [];
        $this->FontFiles = [];
        $this->encodings = [];
        $this->cmaps = [];
        $this->images = [];
        $this->links = [];
        $this->InHeader = false;
        $this->InFooter = false;
        $this->lasth = 0;
        $this->FontFamily = '';
        $this->FontStyle = '';
        $this->FontSizePt = 12;
        $this->underline = false;
        $this->DrawColor = '0 G';
        $this->FillColor = '0 g';
        $this->TextColor = '0 g';
        $this->ColorFlag = false;
        $this->WithAlpha = false;
        $this->ws = 0;
        $this->SetMargins(10, 10);
        $this->cMargin = 2;
        $this->LineWidth = 0.2;
        $this->SetAutoPageBreak(true, 10);
        $this->SetDisplayMode('default');
        $this->SetCompression(true);
        $this->SetTitle('', false);
        $this->SetAuthor('', false);
        $this->SetSubject('', false);
        $this->SetKeywords('', false);
        $this->SetCreator('', false);
        $this->AliasNbPages('{nb}');
        $this->fontpath = dirname(__FILE__).'/';
        $this->CoreFonts = ['courier', 'helvetica', 'times', 'symbol', 'zapfdingbats'];
        $this->StdPageSizes = ['a3'=>[841.89,1190.55],'a4'=>[595.28,841.89],'a5'=>[420.94,595.28],'letter'=>[612,792],'legal'=>[612,1008]];
        // Set scale factor before page size calculation to avoid division by zero
        $this->k = ($unit=='pt') ? 1 : (($unit=='mm') ? 72/25.4 : (($unit=='cm') ? 72/2.54 : (($unit=='in') ? 72 : $this->Error('Incorrect unit: '.$unit))));
        $size = $this->_getpagesize($size);
        $this->DefPageSize = $size;
        $this->CurPageSize = $size;
        $orientation = strtolower($orientation);
        if ($orientation=='p' || $orientation=='portrait') {
            $this->DefOrientation = 'P';
            $this->w = $size[0];
            $this->h = $size[1];
        } elseif ($orientation=='l' || $orientation=='landscape') {
            $this->DefOrientation = 'L';
            $this->w = $size[1];
            $this->h = $size[0];
        } else {
            $this->Error('Incorrect orientation: '.$orientation);
        }
        $this->CurOrientation = $this->DefOrientation;
        $this->wPt = $this->w*$this->k;
        $this->hPt = $this->h*$this->k;
    }

    function SetMargins($left, $top, $right=null)
    {
        $this->lMargin = $left;
        $this->tMargin = $top;
        $this->rMargin = (null === $right) ? $left : $right;
    }

    function SetLeftMargin($margin)
    {
        $this->lMargin = $margin;
        if ($this->page>0 && $this->x<$margin)
            $this->x = $margin;
    }

    function SetTopMargin($margin)
    {
        $this->tMargin = $margin;
    }

    function SetRightMargin($margin)
    {
        $this->rMargin = $margin;
    }

    function SetAutoPageBreak($auto, $margin=0)
    {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h-$margin;
    }

    function SetDisplayMode($zoom, $layout='default')
    {
        if ($zoom=='fullpage' || $zoom=='fullwidth' || $zoom=='real' || $zoom=='default' || !is_string($zoom))
            $this->ZoomMode = $zoom;
        else
            $this->Error('Incorrect zoom display mode: '.$zoom);
        if ($layout=='single' || $layout=='continuous' || $layout=='two' || $layout=='default')
            $this->LayoutMode = $layout;
        else
            $this->Error('Incorrect layout display mode: '.$layout);
    }

    function SetCompression($compress)
    {
        $this->compress = function_exists('gzcompress') ? $compress : false;
    }

    function SetTitle($title, $isUTF8=false)
    {
        $this->title = $isUTF8 ? $title : $this->_UTF8encode($title);
    }

    function SetAuthor($author, $isUTF8=false)
    {
        $this->author = $isUTF8 ? $author : $this->_UTF8encode($author);
    }

    function SetSubject($subject, $isUTF8=false)
    {
        $this->subject = $isUTF8 ? $subject : $this->_UTF8encode($subject);
    }

    function SetKeywords($keywords, $isUTF8=false)
    {
        $this->keywords = $isUTF8 ? $keywords : $this->_UTF8encode($keywords);
    }

    function SetCreator($creator, $isUTF8=false)
    {
        $this->creator = $isUTF8 ? $creator : $this->_UTF8encode($creator);
    }

    function AliasNbPages($alias='{nb}')
    {
        $this->AliasNbPages = $alias;
    }

    function Error($msg)
    {
        throw new Exception('FPDF error: '.$msg);
    }

    function Close()
    {
        if ($this->state==3)
            return;
        if ($this->page==0)
            $this->AddPage();
        $this->InFooter = true;
        $this->Footer();
        $this->InFooter = false;
        $this->_endpage();
        $this->_enddoc();
    }

    function AddPage($orientation='', $size='')
    {
        if ($this->state==0)
            $this->Open();
        $family = $this->FontFamily;
        $style = $this->FontStyle.($this->underline ? 'U' : '');
        $fontsize = $this->FontSizePt;
        $lw = $this->LineWidth;
        $dc = $this->DrawColor;
        $fc = $this->FillColor;
        $tc = $this->TextColor;
        $cf = $this->ColorFlag;
        $ws = $this->ws;

        if ($this->page>0) {
            $this->InFooter = true;
            $this->Footer();
            $this->InFooter = false;
            $this->_endpage();
        }
        $this->_beginpage($orientation, $size);
        $this->_out('2 J');
        $this->LineWidth = $lw;
        $this->_out(sprintf('%.2F w', $lw*$this->k));
        if ($family)
            $this->SetFont($family, $style, $fontsize);
        $this->DrawColor = $dc;
        if ($dc!='0 G')
            $this->_out($dc);
        $this->FillColor = $fc;
        if ($fc!='0 g')
            $this->_out($fc);
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
        if ($ws>0)
            $this->_out(sprintf('%.3F Tw', $ws*$this->k));
        $this->Header();
        if ($this->LineWidth!=$lw) {
            $this->LineWidth = $lw;
            $this->_out(sprintf('%.2F w', $lw*$this->k));
        }
        if ($family)
            $this->SetFont($family, $style, $fontsize);
        if ($this->DrawColor!=$dc) {
            $this->DrawColor = $dc;
            $this->_out($dc);
        }
        if ($this->FillColor!=$fc) {
            $this->FillColor = $fc;
            $this->_out($fc);
        }
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
    }

    function Header()
    {
    }

    function Footer()
    {
    }

    function PageNo()
    {
        return $this->page;
    }

    function SetDrawColor($r, $g=null, $b=null)
    {
        if (($r==0 && $g==0 && $b==0) || $g===null)
            $this->DrawColor = sprintf('%.3F G', $r/255);
        else
            $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r/255, $g/255, $b/255);
        if ($this->page>0)
            $this->_out($this->DrawColor);
    }

    function SetFillColor($r, $g=null, $b=null)
    {
        if (($r==0 && $g==0 && $b==0) || $g===null)
            $this->FillColor = sprintf('%.3F g', $r/255);
        else
            $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
        if ($this->page>0)
            $this->_out($this->FillColor);
    }

    function SetTextColor($r, $g=null, $b=null)
    {
        if (($r==0 && $g==0 && $b==0) || $g===null)
            $this->TextColor = sprintf('%.3F g', $r/255);
        else
            $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
    }

    function GetStringWidth($s)
    {
        $s = (string)$s;
        $cw = $this->CurrentFont['cw'];
        $w = 0;
        $l = strlen($s);
        for ($i=0; $i<$l; $i++)
            $w += $cw[$s[$i]];
        return $w*$this->FontSize/1000;
    }

    function SetLineWidth($width)
    {
        $this->LineWidth = $width;
        if ($this->page>0)
            $this->_out(sprintf('%.2F w', $width*$this->k));
    }

    function Line($x1, $y1, $x2, $y2)
    {
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1*$this->k, ($this->h-$y1)*$this->k, $x2*$this->k, ($this->h-$y2)*$this->k));
    }

    function Rect($x, $y, $w, $h, $style='')
    {
        $op = ($style=='F') ? 'f' : (($style=='FD' || $style=='DF') ? 'B' : 'S');
        $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', $x*$this->k, ($this->h-$y)*$this->k, $w*$this->k, -$h*$this->k, $op));
    }

    function AddFont($family, $style='', $file='')
    {
        $family = strtolower($family);
        if ($file=='')
            $file = str_replace(' ', '', $family).strtolower($style).'.php';
        $style = strtoupper($style);
        if ($style=='IB')
            $style = 'BI';
        $fontkey = $family.$style;
        if (isset($this->fonts[$fontkey]))
            return;
        if (isset($this->FontFiles[$file]))
            $this->Error('Font file already added: '.$file);
        $this->FontFiles[$file] = ['length1'=>0, 'length2'=>0];
        $this->fonts[$fontkey] = ['type'=>'TTF', 'name'=>'', 'desc'=>[], 'up'=>-100, 'ut'=>50, 'cw'=>[], 'file'=>$file];
    }

    function SetFont($family, $style='', $size=0)
    {
        $family = strtolower($family);
        if ($family=='')
            $family = $this->FontFamily;
        if ($family=='arial')
            $family = 'helvetica';
        elseif ($family=='symbol' || $family=='zapfdingbats')
            $style = '';
        $style = strtoupper($style);
        if (strpos($style, 'U')!==false) {
            $this->underline = true;
            $style = str_replace('U', '', $style);
        } else {
            $this->underline = false;
        }
        if ($style=='IB')
            $style = 'BI';
        if ($size==0)
            $size = $this->FontSizePt;
        if ($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size)
            return;
        $fontkey = $family.$style;
        if (!isset($this->fonts[$fontkey])) {
            if (in_array($family, $this->CoreFonts)) {
                if ($family=='symbol' || $family=='zapfdingbats')
                    $style = '';
                $fontkey = $family.$style;
                if (!isset($this->fonts[$fontkey]))
                    $this->AddFont($family, $style);
            } else {
                $this->Error('Undefined font: '.$family.' '.$style);
            }
        }
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size/$this->k;
        $this->CurrentFont = $this->fonts[$fontkey];
        if ($this->page>0)
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
    }

    function SetFontSize($size)
    {
        if ($this->FontSizePt==$size)
            return;
        $this->FontSizePt = $size;
        $this->FontSize = $size/$this->k;
        if ($this->page>0)
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
    }

    function AddLink()
    {
        $n = count($this->links)+1;
        $this->links[$n] = [0, 0];
        return $n;
    }

    function SetLink($link, $y=0, $page=-1)
    {
        if ($y==-1)
            $y = $this->y;
        if ($page==-1)
            $page = $this->page;
        $this->links[$link] = [$page, $y];
    }

    function Link($x, $y, $w, $h, $link)
    {
        $this->PageLinks[$this->page][] = [$x*$this->k, $this->hPt-$y*$this->k, $w*$this->k, $h*$this->k, $link];
    }

    function Text($x, $y, $txt)
    {
        $s = sprintf('BT %.2F %.2F Td (%s) Tj ET', $x*$this->k, ($this->h-$y)*$this->k, $this->_escape($txt));
        if ($this->underline && $txt!='')
            $s .= ' '.$this->_dounderline($x, $y, $txt);
        if ($this->ColorFlag)
            $s = 'q '.$this->TextColor.' '.$s.' Q';
        $this->_out($s);
    }

    function AcceptPageBreak()
    {
        return $this->AutoPageBreak;
    }

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        $k = $this->k;
        if ($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
            $x = $this->x;
            $ws = $this->ws;
            if ($ws>0) {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->AddPage($this->CurOrientation, $this->CurPageSize);
            $this->x = $x;
            if ($ws>0) {
                $this->ws = $ws;
                $this->_out(sprintf('%.3F Tw', $ws*$k));
            }
        }
        if ($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $s = '';
        if ($fill || $border==1)
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x*$k, ($this->h-$this->y)*$k, $w*$k, -$h*$k, $fill ? 'f' : 'S');
        if (is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if (strpos($border, 'L')!==false)
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-$y)*$k, $x*$k, ($this->h-($y+$h))*$k);
            if (strpos($border, 'T')!==false)
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-$y)*$k, ($x+$w)*$k, ($this->h-$y)*$k);
            if (strpos($border, 'R')!==false)
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x+$w)*$k, ($this->h-$y)*$k, ($x+$w)*$k, ($this->h-($y+$h))*$k);
            if (strpos($border, 'B')!==false)
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-($y+$h))*$k, ($x+$w)*$k, ($this->h-($y+$h))*$k);
        }
        if ($txt!=='') {
            if ($align=='R')
                $dx = $w - $this->cMargin - $this->GetStringWidth($txt);
            elseif ($align=='C')
                $dx = ($w - $this->GetStringWidth($txt)) / 2;
            else
                $dx = $this->cMargin;
            if ($this->ColorFlag)
                $s .= 'q '.$this->TextColor.' ';
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', ($this->x+$dx)*$k, ($this->h-($this->y+0.5*$h+0.3*$this->FontSize))*$k, $this->_escape($txt));
            if ($this->underline)
                $s .= ' '.$this->_dounderline($this->x+$dx, $this->y+0.5*$h+0.3*$this->FontSize, $txt);
            if ($this->ColorFlag)
                $s .= ' Q';
            if ($link)
                $this->Link($this->x+$dx, $this->y+0.5*$h-0.5*$this->FontSize, $this->GetStringWidth($txt), $this->FontSize, $link);
        }
        $this->_out($s);
        $this->lasth = $h;
        if ($ln>0) {
            $this->y += $h;
            if ($ln==1)
                $this->x = $this->lMargin;
        } else {
            $this->x += $w;
        }
    }

    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false)
    {
        $cw = $this->CurrentFont['cw'];
        if ($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r", '', (string)$txt);
        $nb = strlen($s);
        $b = 0;
        if ($border) {
            if ($border==1) {
                $border = 'LTRB';
                $b = 'LRT';
                $b2 = 'LR';
            } else {
                $b2 = '';
                if (strpos($border, 'L')!==false)
                    $b2 .= 'L';
                if (strpos($border, 'R')!==false)
                    $b2 .= 'R';
                $b = (strpos($border, 'T')!==false) ? $b2.'T' : $b2;
            }
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $ns = 0;
        $nl = 1;
        while ($i<$nb) {
            $c = $s[$i];
            if ($c=="\n") {
                $this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if ($border && $nl==2)
                    $b = $b2;
                continue;
            }
            if ($c==' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l>$wmax) {
                if ($sep==-1) {
                    if ($i==$j)
                        $i++;
                    $this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
                } else {
                    $this->Cell($w, $h, substr($s, $j, $sep-$j), $b, 2, $align, $fill);
                    $i = $sep+1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if ($border && $nl==2)
                    $b = $b2;
            } else {
                $i++;
            }
        }
        if ($border && strpos($border, 'B')!==false)
            $b .= 'B';
        $this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
        $this->x = $this->lMargin;
    }

    function Output($dest='', $name='', $isUTF8=false)
    {
        if ($this->state<3)
            $this->Close();
        $dest = strtoupper($dest);
        if ($dest=='')
            $dest = 'I';
        if ($name=='')
            $name = 'doc.pdf';
        switch ($dest) {
            case 'I':
                $this->_checkoutput();
                if (PHP_SAPI!='cli') {
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; filename="'.basename($name).'"');
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');
                }
                echo $this->buffer;
                break;
            case 'D':
                $this->_checkoutput();
                header('Content-Type: application/x-download');
                header('Content-Disposition: attachment; filename="'.basename($name).'"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                echo $this->buffer;
                break;
            case 'F':
                $f = fopen($name, 'wb');
                if (!$f)
                    $this->Error('Unable to create output file: '.$name);
                fwrite($f, $this->buffer, strlen($this->buffer));
                fclose($f);
                break;
            case 'S':
                return $this->buffer;
            default:
                $this->Error('Incorrect output destination: '.$dest);
        }
        return '';
    }

    /*************************** Protected methods ***************************/

    protected function _dochecks()
    {
        if (1.1 == 1)
            $this->Error('FPDF requires php 5.4 or above');
        if (ini_get('mbstring.func_overload') && (int)ini_get('mbstring.func_overload') & 2)
            $this->Error('mbstring overloading must be disabled');
    }

    protected function _checkoutput()
    {
        if (PHP_SAPI!='cli' && headers_sent($file, $line))
            $this->Error("Some data has already been output, can't send PDF file (output started at $file:$line)");
    }

    protected function _getpagesize($size)
    {
        if (is_string($size)) {
            $s = strtolower($size);
            if (!isset($this->StdPageSizes[$s]))
                $this->Error('Unknown page size: '.$size);
            $a = $this->StdPageSizes[$s];
            return [$a[0]/$this->k, $a[1]/$this->k];
        } else {
            if (!(is_array($size) && count($size)==2))
                $this->Error('Invalid page size: '.var_export($size, true));
            return [$size[0]/$this->k, $size[1]/$this->k];
        }
    }

    protected function _beginpage($orientation, $size)
    {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';

        if ($orientation=='')
            $orientation = $this->DefOrientation;
        else {
            $orientation = strtoupper($orientation[0]);
            if ($orientation!='P' && $orientation!='L')
                $this->Error('Incorrect orientation: '.$orientation);
        }
        if ($size=='')
            $size = $this->DefPageSize;
        else
            $size = $this->_getpagesize($size);
        if ($orientation!=$this->CurOrientation || $size[0]!=$this->CurPageSize[0] || $size[1]!=$this->CurPageSize[1]) {
            if ($orientation=='P') {
                $this->w = $size[0];
                $this->h = $size[1];
            } else {
                $this->w = $size[1];
                $this->h = $size[0];
            }
            $this->wPt = $this->w*$this->k;
            $this->hPt = $this->h*$this->k;
            $this->PageBreakTrigger = $this->h-$this->bMargin;
            $this->CurOrientation = $orientation;
            $this->CurPageSize = $size;
        }
        if ($orientation!=$this->DefOrientation || $size[0]!=$this->DefPageSize[0] || $size[1]!=$this->DefPageSize[1])
            $this->PageSizes[$this->page] = [$this->wPt, $this->hPt];
    }

    protected function _endpage()
    {
        $this->state = 1;
    }

    protected function _UTF8encode($str)
    {
        if (!function_exists('mb_convert_encoding'))
            return $str;
        if (preg_match('/[\x80-\xFF]/', $str))
            $str = mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
        return $str;
    }

    protected function _escape($s)
    {
        $s = str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', '\\r'], $s);
        return $s;
    }

    protected function _textstring($s)
    {
        return '('.$this->_escape($s).')';
    }

    protected function _dounderline($x, $y, $txt)
    {
        $up = $this->CurrentFont['up'];
        $ut = $this->CurrentFont['ut'];
        $w = $this->GetStringWidth($txt)+$this->ws*substr_count($txt, ' ');
        return sprintf('%.2F %.2F %.2F %.2F re f', $x*$this->k, ($this->h-($y-$up/1000*$this->FontSize))*$this->k, $w*$this->k, -$ut/1000*$this->FontSizePt);
    }

    protected function _parsejpg($file)
    {
        $a = getimagesize($file);
        if (!$a)
            $this->Error('Missing or incorrect image file: '.$file);
        if ($a[2]!=2)
            $this->Error('Not a JPEG file: '.$file);
        if (!isset($a['channels']) || $a['channels']==3)
            $colspace = 'DeviceRGB';
        elseif ($a['channels']==4)
            $colspace = 'DeviceCMYK';
        else
            $colspace = 'DeviceGray';
        $bpc = isset($a['bits']) ? $a['bits'] : 8;
        $data = file_get_contents($file);
        return ['w'=>$a[0], 'h'=>$a[1], 'cs'=>$colspace, 'bpc'=>$bpc, 'f'=>'DCTDecode', 'data'=>$data];
    }

    protected function _parsepng($file)
    {
        $f = fopen($file, 'rb');
        if (!$f)
            $this->Error('Can\'t open image file: '.$file);
        $info = $this->_parsepngstream($f, $file);
        fclose($f);
        return $info;
    }

    protected function _parsepngstream($f, $file)
    {
        if ($this->_readstream($f, 8)!="\x89PNG\r\n\x1A\n")
            $this->Error('Not a PNG file: '.$file);
        $this->_readstream($f, 4);
        if ($this->_readstream($f, 4)!='IHDR')
            $this->Error('Incorrect PNG file: '.$file);
        $w = $this->_readint($f);
        $h = $this->_readint($f);
        $bpc = ord($this->_readstream($f, 1));
        $ct = ord($this->_readstream($f, 1));
        if ($ct==0)
            $colspace = 'DeviceGray';
        elseif ($ct==2)
            $colspace = 'DeviceRGB';
        elseif ($ct==3)
            $colspace = 'Indexed';
        else
            $this->Error('Alpha channel not supported: '.$file);
        if (ord($this->_readstream($f, 1))!=0)
            $this->Error('Unknown compression method: '.$file);
        if (ord($this->_readstream($f, 1))!=0)
            $this->Error('Unknown filter method: '.$file);
        if (ord($this->_readstream($f, 1))!=0)
            $this->Error('Interlacing not supported: '.$file);
        $this->_readstream($f, 4);
        $parms = '/DecodeParms <</Predictor 15 /Colors '.($ct==2 ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
        $trns = '';
        $pal = '';
        $data = '';
        do {
            $n = $this->_readint($f);
            $type = $this->_readstream($f, 4);
            if ($type=='IDAT') {
                $data .= $this->_readstream($f, $n);
            } elseif ($type=='PLTE') {
                $pal = $this->_readstream($f, $n);
            } elseif ($type=='tRNS') {
                $t = $this->_readstream($f, $n);
                if ($ct==0)
                    $trns = [ord(substr($t, 1, 1))];
                elseif ($ct==2)
                    $trns = [ord(substr($t, 1, 1)), ord(substr($t, 3, 1)), ord(substr($t, 5, 1))];
                else {
                    $pos = strpos($t, "\0");
                    if ($pos!==false)
                        $trns = [$pos];
                }
            } elseif ($type=='IEND') {
                break;
            } else {
                $this->_readstream($f, $n);
            }
            $this->_readstream($f, 4);
        } while ($type!='IEND');
        if ($colspace=='Indexed' && $pal=='')
            $this->Error('Missing palette in '.$file);
        $color = ($colspace=='DeviceRGB') ? 3 : 1;
        $data = gzuncompress($data);
        if ($data===false)
            $this->Error('Error while decompressing stream.');
        $data = $this->_pngfilter($data, $w, $h, $color, $bpc);
        if ($colspace=='Indexed') {
            $colspace = 'DeviceRGB';
            $map = '';
            $palLen = strlen($pal);
            for ($i=0; $i<$palLen; $i+=3)
                $map .= sprintf('%c%c%c%c%c%c', 0, $i/3, $pal[$i], $pal[$i+1], $pal[$i+2], 255);
            $trns_data = '';
            if ($trns) {
                foreach ($trns as $key => $value) {
                    $trns_data .= sprintf('%c%c%c%c', 0, $key, 0, $value);
                }
            }
            $data = $map.$trns_data.$data;
            $color = 4;
            $bpc = 8;
        }
        return ['w'=>$w, 'h'=>$h, 'cs'=>$colspace, 'bpc'=>$bpc, 'f'=>'FlateDecode', 'data'=>$data, 'parms'=>$parms, 'trns'=>$trns];
    }

    protected function _readstream($f, $n)
    {
        $res = '';
        while ($n>0) {
            $s = fread($f, $n);
            if ($s===false)
                $this->Error('Error while reading stream.');
            if ($s=='')
                $this->Error('Unexpected end of file.');
            $n -= strlen($s);
            $res .= $s;
        }
        return $res;
    }

    protected function _readint($f)
    {
        $a = unpack('Ni', $this->_readstream($f, 4));
        return $a['i'];
    }

    protected function _parsefont($file)
    {
        include($this->fontpath.$file);
        if (!isset($name))
            $this->Error('Could not include font definition file');
        $info = ['name'=>$name, 'type'=>$type, 'desc'=>$desc, 'up'=>$up, 'ut'=>$ut, 'cw'=>$cw, 'enc'=>$enc, 'diff'=>$diff, 'file'=>$file, 'ctg'=>$ctg];
        if (!empty($file)) {
            if (!isset($this->FontFiles[$file]))
                $this->FontFiles[$file] = ['length1'=>$originalsize, 'length2'=>0];
        }
        return $info;
    }

    protected function _newobj()
    {
        $this->n++;
        $this->offsets[$this->n] = strlen($this->buffer);
        $this->_out($this->n.' 0 obj');
    }

    protected function _putstream($s)
    {
        if ($this->compress)
            $s = gzcompress($s);
        $this->_out('<< /Length '.strlen($s).' >>');
        $this->_out('stream');
        $this->_out($s);
        $this->_out('endstream');
    }

    protected function _out($s)
    {
        if ($this->state==2)
            $this->pages[$this->page] .= $s."\n";
        else
            $this->buffer .= $s."\n";
    }

    protected function _putpages()
    {
        $nb = $this->page;
        if (!empty($this->AliasNbPages)) {
            for ($n=1; $n<=$nb; $n++)
                $this->pages[$n] = str_replace($this->AliasNbPages, $nb, $this->pages[$n]);
        }
        if ($this->DefOrientation=='P') {
            $wPt = $this->DefPageSize[0]*$this->k;
            $hPt = $this->DefPageSize[1]*$this->k;
        } else {
            $wPt = $this->DefPageSize[1]*$this->k;
            $hPt = $this->DefPageSize[0]*$this->k;
        }
        $filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
        for ($n=1; $n<=$nb; $n++) {
            $this->_newobj();
            $this->_out('<</Type /Page');
            $this->_out('/Parent 1 0 R');
            if (isset($this->PageSizes[$n]))
                $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $this->PageSizes[$n][0], $this->PageSizes[$n][1]));
            else
                $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $wPt, $hPt));
            $this->_out('/Resources 2 0 R');
            $this->_out('/Contents '.($this->n+1).' 0 R>>');
            $this->_out('endobj');
            $this->_newobj();
            $p = ($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];
            $this->_out('<<'.$filter.'/Length '.strlen($p).'>>');
            $this->_out('stream');
            $this->_out($p);
            $this->_out('endstream');
            $this->_out('endobj');
        }
        $this->offsets[1] = strlen($this->buffer);
        $this->_out('1 0 obj');
        $this->_out('<</Type /Pages');
        $kids = '/Kids [';
        for ($n=1; $n<=$nb; $n++)
            $kids .= (3 + 2 * ($n - 1)).' 0 R ';
        $kids .= ']';
        $this->_out($kids);
        $this->_out('/Count '.$nb);
        $this->_out('>>');
        $this->_out('endobj');
    }

    protected function _putfonts()
    {
        $nf = $this->n;
        foreach ($this->fonts as $fontkey => $font) {
            $this->_newobj();
            $this->fonts[$fontkey]['i'] = $this->n;
            $this->_out('<</Type /Font');
            $this->_out('/BaseFont /'.str_replace(' ', '', $fontkey));
            $this->_out('/Subtype /Type1');
            $this->_out('/Encoding /WinAnsiEncoding');
            $this->_out('>>');
            $this->_out('endobj');
        }
    }

    protected function _putimages()
    {
        foreach ($this->images as $file => $info) {
            $this->_newobj();
            $this->images[$file]['i'] = $this->n;
            $this->_out('<</Type /XObject');
            $this->_out('/Subtype /Image');
            $this->_out('/Width '.$info['w']);
            $this->_out('/Height '.$info['h']);
            if ($info['cs']=='Indexed')
                $this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
            else {
                $this->_out('/ColorSpace /'.$info['cs']);
                if ($info['cs']=='DeviceCMYK')
                    $this->_out('/Decode [1 0 1 0 1 0 1 0]');
            }
            $this->_out('/BitsPerComponent '.$info['bpc']);
            if (isset($info['f']))
                $this->_out('/Filter /'.$info['f']);
            if (isset($info['dp']))
                $this->_out('/DecodeParms << /Predictor 15 /Colors '.($info['cs']=='DeviceRGB' ? 3 : 1).' /BitsPerComponent '.$info['bpc'].' /Columns '.$info['w'].' >>');
            if (isset($info['trns']) && is_array($info['trns'])) {
                $trns = '';
                for ($i=0; $i<count($info['trns']); $i++)
                    $trns .= $info['trns'][$i].' '.$info['trns'][$i].' ';
                $this->_out('/Mask ['.$trns.']');
            }
            $this->_out('/Length '.strlen($info['data']).'>>');
            $this->_putstream($info['data']);
            $this->_out('endobj');
            if ($info['cs']=='Indexed') {
                $this->_newobj();
                $pal = ($this->compress) ? gzcompress($info['pal']) : $info['pal'];
                $this->_out('<< /Length '.strlen($pal).' >>');
                $this->_out('stream');
                $this->_out($pal);
                $this->_out('endstream');
                $this->_out('endobj');
            }
        }
    }

    protected function _putresourcedict()
    {
        $this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
        $this->_out('/Font <<');
        foreach ($this->fonts as $fontkey => $font)
            $this->_out('/F'.$font['i'].' '.($font['i']).' 0 R');
        $this->_out('>>');
        if (count($this->images)) {
            $this->_out('/XObject <<');
            foreach ($this->images as $file => $info)
                $this->_out('/I'.$info['i'].' '.($info['i']).' 0 R');
            $this->_out('>>');
        }
    }

    protected function _putresources()
    {
        $this->_putfonts();
        $this->_putimages();
        $this->_newobj();
        $this->_out('2 0 obj');
        $this->_out('<<');
        $this->_putresourcedict();
        $this->_out('>>');
        $this->_out('endobj');
    }

    protected function _putinfo()
    {
        $this->_out('/Producer '.$this->_textstring('FPDF 1.85'));
        if ($this->title)
            $this->_out('/Title '.$this->_textstring($this->title));
        if ($this->subject)
            $this->_out('/Subject '.$this->_textstring($this->subject));
        if ($this->author)
            $this->_out('/Author '.$this->_textstring($this->author));
        if ($this->keywords)
            $this->_out('/Keywords '.$this->_textstring($this->keywords));
        if ($this->creator)
            $this->_out('/Creator '.$this->_textstring($this->creator));
    }

    protected function _putcatalog()
    {
        $this->_out('/Type /Catalog');
        $this->_out('/Pages 1 0 R');
        if ($this->ZoomMode=='fullpage')
            $this->_out('/OpenAction [3 0 R /Fit]');
        elseif ($this->ZoomMode=='fullwidth')
            $this->_out('/OpenAction [3 0 R /FitH null]');
        elseif ($this->ZoomMode=='real')
            $this->_out('/OpenAction [3 0 R /XYZ null null 1]');
        elseif (!is_string($this->ZoomMode))
            $this->_out('/OpenAction [3 0 R /XYZ null null '.($this->ZoomMode/100).']');
        if ($this->LayoutMode=='single')
            $this->_out('/PageLayout /SinglePage');
        elseif ($this->LayoutMode=='continuous')
            $this->_out('/PageLayout /OneColumn');
        elseif ($this->LayoutMode=='two')
            $this->_out('/PageLayout /TwoColumnLeft');
    }

    protected function _puttrailer()
    {
        $this->_out('/Size '.($this->n+1));
        $this->_out('/Root '.($this->n).' 0 R');
        $this->_out('/Info '.($this->n-1).' 0 R');
    }

    protected function _enddoc()
    {
        $this->_putheader();
        $this->_putpages();
        $this->_putresources();
        $this->_newobj();
        $this->_out('<<');
        $this->_putinfo();
        $this->_out('>>');
        $this->_out('endobj');
        $this->_newobj();
        $this->_out('<<');
        $this->_putcatalog();
        $this->_out('>>');
        $this->_out('endobj');
        $o = strlen($this->buffer);
        $this->_out('xref');
        $this->_out('0 '.($this->n+1));
        $this->_out('0000000000 65535 f ');
        for ($i=1; $i<=$this->n; $i++)
            $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
        $this->_out('trailer');
        $this->_out('<<');
        $this->_puttrailer();
        $this->_out('>>');
        $this->_out('startxref');
        $this->_out($o);
        $this->_out('%%EOF');
        $this->state = 3;
    }

    protected function _putheader()
    {
        $this->_out('%PDF-'.$this->PDFVersion);
    }
}
