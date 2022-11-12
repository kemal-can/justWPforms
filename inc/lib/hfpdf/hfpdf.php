<?php

require_once( dirname( __FILE__ ) . '/tfpdf.php' );

class hFPDF extends tFPDF {

	protected $widths = array();
	protected $paddings = array();
	protected $lineHeight = 1;
	protected $headerMargin = 0;
	protected $footerTopMargin = 0;
	protected $footerBottomMargin = 1;
	protected $footerHeight = 0;
	protected $footerContent = '';
	protected $fontSizeNormal = 12;
	protected $fontSizeBig = 18;
	protected $logoMargin = 5;
	protected $titleMargin = 5;
	protected $contentMargin = 5;
	protected $contentFooterMargin = 10;
	protected $stripeColors = array();

	public function __construct() {
		parent::__construct();

		$this->SetMargins( 20, 30, 20 );
		$this->SetPaddings( 4, 2, 4, 2 );
		$this->SetWidths( 30, 70 );
		$this->AddFont( 'Noto', 'NotoSans-Regular.ttf', true );
		$this->SetFont( 'Noto' );
		$this->SetLineHeight( 1.5 );
		$this->SetFontSizeNormal( 12 );
		$this->SetFontSizeBig( 18 );
		$this->SetStripeColors();
		$this->setHeaderMargin( 5 );
		$this->setFooterTopMargin( 5 );
		$this->setFooterBottomMargin( 30 );
		$this->AliasNbPages();
		$this->AddPage();
	}

	public function SetWidths( $left = 30, $right = 70 ) {
		$width = $this->w - $this->lMargin - $this->rMargin;
		$left = floor( $left / 100 * $width );
		$right = floor( $right / 100 * $width );

		$this->widths = array( $left, $right );
	}

	public function SetPaddings( $top = 0, $right = 0, $bottom = 0, $left = 0 ) {
		$this->paddings = array( $top, $right, $bottom, $left );
	}

	public function SetLineHeight( $lineHeight = 1 ) {
		$this->lineHeight = $lineHeight;
	}

	public function SetFontSizeNormal( $size = 12 ) {
		$this->fontSizeNormal = $size;
	}

	public function SetFontSizeBig( $size = 18 ) {
		$this->fontSizeBig = $size;
	}

	public function SetHeaderMargin( $headerMargin = 0 ) {
		$this->headerMargin = $headerMargin;
	}

	public function SetFooterTopMargin( $footerTopMargin = 0 ) {
		$this->footerTopMargin = $footerTopMargin;
	}

	public function SetFooterBottomMargin( $footerBottomMargin = 1 ) {
		$this->footerBottomMargin = $footerBottomMargin;
	}

	public function SetFooterContent( $content = '' ) {
		$this->footerContent = $content;

		$lineCount = $this->NbLines( $this->CurPageSize[0], $content );
		$this->footerHeight = $this->FontSize * $this->lineHeight * $lineCount + $this->footerTopMargin + $this->footerBottomMargin;
		$this->SetAutoPageBreak( true, $this->footerHeight );
	}

	public function SetStripeColors( $colors = array() ) {
		if ( empty( $colors ) ) {
			$colors = array(
				array( 229, 229, 229 ),
				array( 255, 255, 255 ),
			);
		}

		$this->stripeColors = $colors;
	}

	public function Row( $data ) {
		$horizontalPadding = $this->paddings[1] + $this->paddings[3];
		$verticalPadding = $this->paddings[0] + $this->paddings[2];
		$rowHeight = 0;

		$file_path = $data[1];
		$hasImage = @file_exists( $file_path );
		$image_size = array();
		$image_width = 0;
		$image_height = 0;

		// Calculate the height of the row
		if ( ! $hasImage ) {
			$lineCount = 0;
			
			for( $i = 0; $i < count( $data ); $i ++ ) {
				$width = $this->widths[$i] - $horizontalPadding;
				$lineCount = max( $lineCount, $this->NbLines( $width, $data[$i] ) );
			}

			$rowHeight = $this->FontSize * $this->lineHeight * $lineCount + $verticalPadding;
		} else {
			list( $image_width, $image_height ) = $this->fitImageSize( $file_path, $this->widths[1] - $horizontalPadding, 50 );
			$width = $this->widths[0] - $horizontalPadding;

			$leftLineCount = $this->NbLines( $width, $data[0] );
			$leftRowHeight = $this->FontSize * $this->lineHeight * $leftLineCount + $verticalPadding;
			// All images default to 50 height
			$rowHeight = max( $leftRowHeight, $image_height + $this->paddings[0] + $this->paddings[2] );
		}
		
		// Issue a page break first if needed
		$this->CheckPageBreak( $rowHeight );

		// Draw the border
		$rowWidth = $this->w - $this->lMargin - $this->rMargin;
		$this->Rect( $this->lMargin, $this->GetY(), $rowWidth, $rowHeight, 'F' );

		// Draw the cells of the row
		if ( ! $hasImage ) {
			for( $i = 0; $i < count( $data ); $i ++ ) {
				$w = $this->widths[$i];
				// Save the current position
				$x = $this->GetX();
				$y = $this->GetY();
				// Print the text
				$this->SetXY( $x + $this->paddings[3], $y + $this->paddings[0] );
				$this->MultiCell( $w - $horizontalPadding, $this->FontSize * $this->lineHeight, $data[$i], 0, 'L' );
				//Put the position to the right of the cell
				$this->SetXY( $x + $w, $y );
			}
		} else {
			$w = $this->widths[0];
			$x = $this->GetX();
			$y = $this->GetY();
			$this->SetXY( $x + $this->paddings[3], $y + $this->paddings[0] );
			$this->MultiCell( $w - $horizontalPadding, $this->FontSize * $this->lineHeight, $data[0], 0, 'L' );
			$this->SetXY( $x + $w, $y );
			$x = $this->GetX();
			$y = $this->GetY();
			$this->SetXY( $x + $this->paddings[3], $y + $this->paddings[0] );
			$this->Image( $file_path, $this->getX(), $this->getY(), $image_width, $image_height );
		}

		// Go to the next line
		$this->Ln( $rowHeight );
	}

	protected function CheckPageBreak( $h ) {
		// If the height h would cause an overflow, add a new page immediately
		if ( $this->GetY() + $h > $this->PageBreakTrigger ) {
			$this->AddPage( $this->CurOrientation );
		}
	}

	protected function NbLines($w,$txt) {
		// Computes the number of lines a MultiCell of width w will take
		$cw = &$this->CurrentFont['cw'];

		if( $w == 0 ) {
			$w = $this->w - $this->rMargin - $this->x;
		}

		$wmax = ( $w - 2 * $this->cMargin ) * 1000 / ( $this->FontSize * $this->lineHeight );
		$s = str_replace( "\r", '', $txt );
		$nb = strlen( $s );

		if( $nb > 0 && $s[$nb-1] == "\n" ) {
			$nb --;
		}

		$sep = -1;
		$i = 0;
		$j = 0;
		$l = 0;
		$nl = 1;

		while( $i < $nb ) {
			$c = $s[$i];

			if ( $c == "\n" ) {
				$i ++;
				$sep = -1;
				$j = $i;
				$l = 0;
				$nl ++;
				continue;
			}

			if ( $c == ' ' ) {
				$sep = $i;
			}

			$l += $this->GetStringWidth( $c ) / ( $this->FontSize * $this->lineHeight ) * 1000;

			if ( $l > $wmax ) {
				if ( $sep == -1 ) {
					if( $i == $j ) {
						$i++;
					}
				} else {
					$i = $sep + 1;
				}

				$sep = -1;
				$j = $i;
				$l = 0;
				$nl ++;
			} else {
				$i++;
			}
		}

		return $nl;
	}

	public function Header() {
		$this->SetFontSize( $this->fontSizeNormal );

		// Calculate date width
		$date = date_i18n( 'M j, Y', current_time( 'timestamp' ) );
		$date_width = $this->GetStringWidth( $date );

		// Calculate pagination width
		$pagination = $this->PageNo() . '/{nb}';
		$pagination_width = $this->GetStringWidth( $pagination );
		$cell_width = max( $date_width, $pagination_width );

		// Output date
		$this->SetX( $this->w - $this->rMargin - $cell_width );
		$this->Cell( $cell_width, $this->FontSize * $this->lineHeight, $date, 0, 1, 'L' );

		// Output page numbers
		$this->SetX( $this->w - $this->rMargin - $cell_width );
		$this->Cell( $cell_width, $this->FontSize * $this->lineHeight, $pagination, 0, 1, 'L' );
		$this->SetY( $this->getY() + $this->headerMargin );
	}

	public function fitImageSize( $file_path, $max_width, $max_height ) {
		$ratio = 3.78;
		$max_width = $max_width * $ratio;
		$max_height = $max_height * $ratio;

		$image_size = @getimagesize( $file_path );
		$image_width = $image_size[0];
		$image_height = $image_size[1];

		if ( $image_width > $max_width ) {
			$image_height = $image_height * $max_width / $image_width;
			$image_width = $max_width;
		}

		if ( $image_height > $max_height ) {
			$image_width = $image_width * $max_height / $image_height;
			$image_height = $max_height;
		}

		$image_width = $image_width / $ratio;
		$image_height = $image_height / $ratio;
		$image_size = array( $image_width, $image_height );

		return $image_size;
	}

	public function OutputLogo( $file, $height ) {
		$max_width = 642;
		$max_height = 188;
		$ratio = 3.75;
		
		$image_size = @getimagesize( $file );
		$image_width = $image_size[0];
		$image_height = $image_size[1];

		if ( $image_width > $max_width ) {
			$image_height = $image_height * $max_width / $image_width;
			$image_width = $max_width;
		}

		if ( $image_height > $max_height ) {
			$image_width = $image_width * $max_height / $image_height;
			$image_height = $max_height;
		}

		$image_width = $image_width / $ratio;
		$image_height = $image_height / $ratio;

		$this->Image( $file, $this->getX(), $this->getY(), $image_width, $image_height );
		$this->SetY( $this->getY() + $height + $this->logoMargin );
	}

	public function OutputTitle( $title ) {
		$this->SetFontSize( $this->fontSizeBig );
		$this->MultiCell( 0, $this->FontSize * $this->lineHeight, $title, 0, 'L' );
		$this->SetY( $this->getY() + $this->titleMargin );
	}

	public function OutputContent( $content ) {
		$this->SetFontSize( $this->fontSizeNormal );
		$this->MultiCell( 0, $this->FontSize * $this->lineHeight, $content, 0, 'L' );
		$this->SetY( $this->getY() + $this->contentMargin );
	}

	public function OutputContentFooter( $content ) {
		$this->SetY( $this->getY() + $this->contentFooterMargin );
		$this->SetFontSize( $this->fontSizeNormal );
		$this->MultiCell( 0, $this->FontSize * $this->lineHeight, $content, 0, 'L' );
	}

	public function OutputSubmissionData( $data ) {
		foreach( $data as $r => $row ) {
			$c = $r % count( $this->stripeColors );
			$color = $this->stripeColors[$c];
			call_user_func_array( array( $this, 'SetFillColor' ), $color );
			$this->Row( $row );
		}
	}

	public function Footer() {
		$this->SetFontSize( $this->fontSizeNormal );
		$this->SetY( $this->PageBreakTrigger + $this->footerTopMargin );
		$this->MultiCell( 0, $this->FontSize * $this->lineHeight, $this->footerContent, 0, 'L' );
	}

}
