<?php

  /**
   * Klasa generująca fakture na podstawię modelu danych.
   *
   * @author Marcin Czołnowski
   * @license http://creativecommons.org/licenses/by-nd/3.0/deed.en
   * @version v0.2
   */

  // Import klasy abstrakcyjnej implementującej szablon modelu.
  require_once 'models/Invoice.php';
  
  class InvoiceGenerator {

    /**
     * Flaga informująca czy klasa ma wysyłać logi do konsoli na temat aktywności.
     * @var boolean
     */
    public $silent = false;

    /**
     * Instancja pdfa.
     * @var FPDF
     */
    protected $pdf = null;

    /**
     * Instancja modelu faktury.
     * @var IInvoice
     */
    protected $invoice = null;

    /**
     * Informację o arkuszu.
     * @var array
     */
    protected $sheet = array(
      'width' => 209.9,
      'height' => 296.9,
      'unit' => 0.3528,
      'margin' => array(
        'left' => 0, 'right' => 0,
        'top' => 0, 'bottom' => 0
      )
    );
  
    /**
     * Obecna strona.
     * @var int
     */
    protected $page = 1;
    
    /**
     * Lista "modułów", które mają zostać wygenerowane na fakturzę.
     * Poprzez moduł oznaczana jest kolejna metoda rozpoczynająca się od frazy `draw`.
     * @var array
     */
    protected $gen_steps = array(
      array( 'header', 40 ), array( 'salesInfo', 10 ), array( 'dealSidesInfo', 20 ),
      'services', 'prices', 'info', 'sign'
    );
    
    /**
     * Konstruktor generatora, tworzy instancje pdfa oraz przyjmuję instancję modelu.
     * @param Invoice $invoice Instancja modelu faktury według którego faktura ma zostać wygenerowana.
     * @param boolean $silent Czy ma zostać włączony tryb logowania aktywności.
     * @throws InvalidArgumentException
     */
    public function __construct( Invoice $invoice, $silent = false) {
    
      if( $silent === true )
        $this -> silent = true;
    
      $this -> dump( 'new InvoiceGenerator() v0.2; a. Marcin Czołnowski, CC' );

      // Import klasy do generowania pdfów.
      require_once 'fpdf/fpdf.php';
      define( 'FPDF_FONTPATH', realpath( '.' ) . '/fpdf/font/' );
      $this -> dump( 'require fpdf -> OK!' );
      
      $this -> pdf = new FPDF();
      
      $validModel = $this -> checkModel( $invoice );
      if( $validModel !== true )
        throw new InvalidArgumentException( 'Invalid model! [' . $validModel . ']' );

      $this -> dump( 'valid_model() -> OK!' );
        
      $this -> invoice = $invoice;
      $this -> init();
    
    }

    /**
     * Akcja generująca pdfa i zapisująca go do podanego pliku.
     * @param string $fileName Ścieżka do pdfa.
     * @return InvoiceGenerator
     */
    public function generate( $fileName ) {
    
      $this -> dump( 'generate() -> OK!' );
    
      $steps = '$this -> ';
      foreach( $this -> gen_steps as $i => $step )
        $steps .= 'draw' . ( is_array( $step ) ? ucfirst( $step[ 0 ] ) : ucfirst( $step )) .
          '()' . (
            $i + 1 < count( $this -> gen_steps ) ?
              ' -> drawSeparator(' .
                ( is_array( $step ) && isset( $step[ 1 ]) && is_int( $step[ 1 ])
                  ? ' ' . $step[ 1 ]. ' ' : '' ) . ') -> ' : ''
            );
      $steps .= ' -> drawFooter()';
      eval( $steps . ';' );
      
      $this -> dump( 'to output -> OK!' );
      $this -> pdf -> Output( $fileName, 'F' );
      return $this;
    
    }
    
    /**
     * Tworzenie nagłówka faktury.
     * @param array $options Opcję nagłówka.
     * @return InvoiceGenerator
     */
    protected function drawHeader( $options = array()) {
    $type = $this -> invoice -> type;
      $options = $this -> prepareOptions(
        $options, array( 'fontSizes' => array( 'header' => 16, 'type' => 12 ))
      );
      
      $this -> dump( 'draw: header() -> OK!' );
      
      // Konwersja nagłówka
      list( $header, $headerWidth, $headerHeight ) = $this -> prepareString(
        sprintf( $this -> invoice -> headerText, $this -> invoice -> number ),
        $options[ 'fontSizes' ][ 'header' ]
      );

      $type = $this -> invoice -> type;
      if( !empty( $type ))
        list( $type, $typeWidth, $typeHeight ) = $this -> prepareString(
          $type, $options[ 'fontSizes' ][ 'type' ]
        );

      if( $this -> checkPagebreak( $headerHeight + $typeHeight ))
        $this -> pageBreak();

      $this -> pdf -> Cell(
        $headerWidth, $headerHeight, $header, 0 , 1
      );
      
      
      if( !empty( $type )) {

        $this -> dump( 'draw: sub_hedaer() -> OK!' );
        $this -> pdf -> Cell(
          $headerWidth, $typeHeight, $type, 0, 0, 'C'
        );
      
      }

      $this -> dump( 'draw header complete -> OK!' );
      return $this;
    
    }
    
    /**
     * Rysowanie informację o transakcji.
     * @param array $options Opcję transkacji.
     * @return InvoiceGenerator
     */
    protected function drawSalesInfo( $options = array()) {
    
      $options = $this -> prepareOptions(
        $options, array(
          'fontSizes' => array( 'label' => 10, 'value' => 10 ), 'interline' => 1.5
        )
      );
      
      $this -> dump( 'draw: sales_info() -> OK!' );
      
      $data = array(
        'labels' => array(
          'Miejsce wystawienia', 'Data wystawienia', 'Data sprzedazy'
        ), 'values' => array(
          $this -> invoice -> issuePlace, $this -> invoice -> issueDate,
          $this -> invoice -> salesDate
        )
      );
    
      $leftMax = 0;
      for( $i = 0; $i < 3; ++$i ) {
        
        list( $data[ 'labels' ][ $i ], $width ) = $this -> prepareString(
          $data[ 'labels' ][ $i ] . ': ', $options[ 'fontSizes' ][ 'label' ]
        );

        if( $width > $leftMax )
          $leftMax = $width;

        list( $data[ 'values' ][ $i ] ) = $this -> prepareString(
          $data[ 'values' ][ $i ], $options[ 'fontSizes' ][ 'value' ]
        );
       
      }
      $leftMax *= 1.2;

      $labelHeight = $options[ 'fontSizes' ][ 'label' ] * $this -> sheet[ 'unit' ];
      $valueHeight = $options[ 'fontSizes' ][ 'value' ] * $this -> sheet[ 'unit' ];
      $height = ( $labelHeight > $valueHeight ?
          $labelHeight : $valueHeight ) * $options[ 'interline' ];

      if( $this -> checkPagebreak( 3 * $height ))
        $this -> pageBreak();

      for( $i = 0; $i < 3; ++$i ) {

        $this -> pdf -> Cell(
          $leftMax, $height, $data[ 'labels' ][ $i ], 0, 0, 'R'
        );
        
        $this -> pdf -> Cell(
          0, $height, $data[ 'values' ][ $i ], 0, 1
        );
        
      }
      
      $this -> dump( 'draw sales info complete -> OK!' );
      
      return $this;
    
    }
    
    /**
     * Rysowanie tabeli sprzedawcy i nabywcy.
     * @param array $options Opcję tabeli sprzedawcy i nabywcy.
     * @return InvoiceGenerator
     */
    protected function drawDealSidesInfo( $options = array()) {
    
      $options  = $this -> prepareOptions(
        $options, array(
          'fontSizes' => array( 'seller' => 12, 'buyer' => 12 ),
          'interline' => array(
            'header' => 1.25, 'seller' => 1.2, 'buyer' => 1.2
          )
        )
      );
      
      $this -> dump( 'draw: deal_sides_info() -> OK!' );
        
      $data = array(
        'seller' => array( 'Sprzedawca' ),
        'buyer' => array( 'Nabywca' )
      );
      foreach( array( 'name', 'address', 'zip_city', 'nip' ) as $h )
        foreach( array( 'seller', 'buyer' ) as $b )
          $data[ $b ][] = $this -> invoice -> { $b } -> { $h };        
      
      $width = ( $this -> sheet[ 'width' ] - 2 * $this -> sheet[ 'margin' ][ 'left' ] ) / 2;
      $height = array(
        'seller' => $options[ 'fontSizes' ][ 'seller' ] * $this -> sheet[ 'unit' ] * $options[ 'interline' ][ 'seller' ],
        'buyer' => $options[ 'fontSizes' ][ 'buyer' ] * $this -> sheet[ 'unit' ] * $options[ 'interline' ][ 'buyer' ]
      );

      $sumHeight = array(
        'seller' => 4 * $height[ 'seller' ] + $height[ 'seller' ] * $options[ 'interline' ][ 'header' ],
        'buyer' => 4 * $height[ 'buyer' ] + $height[ 'buyer' ] * $options[ 'interline' ][ 'header' ]
      );

      if( $this -> checkPagebreak( $sumHeight[ 'seller' ] > $sumHeight[ 'buyer' ] ? $sumHeight[ 'seller' ] : $sumHeight[ 'buyer' ]))
        $this -> pageBreak();

      $this -> dump( 'prepare data -> OK!' );
      for( $i = 0; $i < 5; ++$i ) {
        
        list( $data[ 'seller' ][ $i ] ) = $this -> prepareString(
          $data[ 'seller' ][ $i ] . ( $i == 0 ? ': ' : '' ), $options[ 'fontSizes' ][ 'seller' ]
        );
        
        list( $data[ 'buyer' ][ $i ] ) = $this -> prepareString(
          $data[ 'buyer' ][ $i ] . ( $i == 0 ? ': ' : '' ), $options[ 'fontSizes' ][ 'buyer' ]
        );
        
        if( $i == 0 )
          $this -> pdf -> SetFont( 'arialpl', 'B' );
        else
          $this -> pdf -> SetFont( 'arialpl', '' );

        $this -> pdf -> Cell(
          $width, $height[ 'seller' ] * ( $i == 0 ? $options[ 'interline' ][ 'header' ] : 1 ),
          $data[ 'seller' ][ $i ]
        );
        $this -> pdf -> Cell(
          $width, $height[ 'buyer' ] * ( $i == 0 ? $options[ 'interline' ][ 'header' ] : 1 ),
          $data[ 'buyer' ][ $i ], 0, 1
        );
        
      }
    
      $this -> dump( 'draw deal sides info complete -> OK!' );
      return $this;
    
    }
    
    /**
     * Rysowanie tabeli usług.
     * @param array $options Opcje wejściowe dla tabeli usług.
     * @return InvoiceGenerator
     */
    private function drawServices( $options = array()) {
    
      $options = $this -> prepareOptions(
        $options, array( 'margins' => array( 'x' => .5, 'y' => .5 ))
      );
    
      $this -> dump( 'draw: services -> OK!' );
    
      /**
       * Zawartość tabeli w postaci macierzy.
       * @var array
       */
      $table = array_merge(
        array( 
          array_merge(
            array( 'LP' ), $this -> invoice -> serviceHeaders,
            array( 'Wartość netto' )
        )), $this -> invoice -> services
      );
      
      /**
       * Opcję tabeli.
       * @var array
       */
      $tableOptions = array_merge(
        array( array( 'align' => 'right' )),
        $this -> invoice -> serviceOptions,
        array( array( 'align' => 'center', 'price' => true ))
      );

      $this -> dump( 'services data collected -> OK!');
      
      /**
       * Szerokości poszczególnych komórek.
       * @var array
       */
      $widths = array();
    
      /**
       * Wielkość czcionki tabeli.
       * @var int
       */
      $font = 10;
    
      /**
       * Zakresy wielkości czcionki.
       * @var stdClass
       */
      $fontRange = array(
        'from' => $font, 'to' => 0
      );

      /**
       * Wyszukiwanie wielkości czcionki dla której tabelka będzie czytelna. (nie za szeroka?)
       */
      for( $fontSize = $fontRange[ 'from' ]; $fontSize > $fontRange[ 'to' ]; --$fontSize ) {
        
        // Marginesy dla komórek.
        $margins = array(
          'x' => $fontSize * $options[ 'margins' ][ 'x' ],
          'y' => $fontSize * $options[ 'margins' ][ 'y' ]
        );
        
        // Łączna wysokość komórki.
        $cellHeight = $fontSize * $this -> sheet[ 'unit' ];
        
        // Łączna wysokość lewego/prawego separatora.
        $sepHeight = 2 * $margins[ 'y' ] + $fontSize * $this -> sheet[ 'unit' ];

        /**
         * Iteracja po wszystkich elementach tabeli usług.
         */
        foreach( $table as $irow => $row ) {
          
          $ntable[ $irow ] = array();
          
          if( $irow < 1 )
            $this -> pdf -> SetFont( 'arialpl', 'B' );
          elseif( $irow == 1 )
            $this -> pdf -> SetFont( 'arialpl', '' );
            
          foreach( $row as $icell => $cell ) {        

            $ntable[ $irow ][ $icell ] = null;
            list(
              $ntable[ $irow ][ $icell ], $width
            ) = $this -> prepareString(
              $irow > 0 && is_array( $tableOptions[ $icell ] ) && 
              isset( $tableOptions[ $icell ][ 'price' ] ) && $tableOptions[ $icell ][ 'price' ] === true ?
                $this -> makePrice( (float) $cell ) : $cell, $fontSize
            );
            
            if( !isset( $widths[ $icell ] ) || $width > $widths[ $icell ] )
              $widths[ $icell ] = $width;
            
          }
          
        }

        // Sprawdzanie czy tabela zmieści się na szerokość strony. 
        $tableWidth = array_sum( $widths ) + count( $widths ) * 2 * $margins[ 'x' ];
        if( $tableWidth < $this -> sheet[ 'width' ] - 2 * $this -> sheet[ 'margin' ][ 'left' ]) {

          $this -> pdf -> SetFontSize( $fontSize );
          $font = $fontSize;
          $this -> dump( 'services font: ' . $fontSize . ' -> OK!' );
          
          array_unshift( $ntable, array());
          foreach( $table as $irow => $row )
            if( $irow != 0 )
              break;
            else
              foreach( $row as $icell => $cell )
                list( $ntable[ 0 ][ $icell ] ) = $this -> prepareString(
                  $cell, $font
                );
          
          //$ntable = array_merge(
          //  array( $table[ 0 ] ), $ntable
          //);
          
          break;
          
        }
        
        // Zerowanie szerokości ze względu na brak trafienia.
        $widths = array();

        $ntable = array();
        
      }

      /**
       * Marginesy tabeli - aby ją wyśrodkować.
       * @var float
       */
      $tableMargin = $tableWidth < $this -> sheet[ 'width' ] - 2 * $this -> sheet[ 'margin' ][ 'left' ] ?
        (( $this -> sheet[ 'width' ] - 2 * $this -> sheet[ 'margin' ][ 'left' ] ) - $tableWidth ) / 2 : 0;
      
      $this -> dump( 'draw service table -> OK!' );
      
      /**
       * Iteracja po tabeli i jej rysowanie.
       */
      foreach( $ntable as $irow => $row ) {
        
        $this -> pdf -> SetXY(
          $this -> pdf -> GetX() + $tableMargin, $this -> pdf -> getY()
        );
        $this -> pdf -> Cell(
          $tableWidth, 0, '', 'T', 1
        );
        
        if( $this -> checkPagebreak( $sepHeight )) {
          
          $this -> pageBreak();
          $this -> pdf -> SetXY(
            $this -> pdf -> GetX() + $tableMargin, $this -> pdf -> getY()
          );
          $this -> pdf -> Cell(
            $tableWidth, 0, '', 'T', 1
          );
          $this -> pdf -> SetFontSize( $font );
          
        }
        
        if( $irow < 1 )          
          $this -> pdf -> SetFont( 'arialpl', 'B' );
        elseif( $irow == 1)
          $this -> pdf -> SetFont( 'arialpl', '' );
        
        foreach( $row as $icell => $cell ) {
  
          if( $icell < 1)
            $this -> pdf -> SetXY(
              $this -> pdf -> GetX() + $tableMargin, $this -> pdf -> getY()
            );

          // Margines lewy
          $this -> pdf -> Cell(
            $margins[ 'x' ], $sepHeight, '', $icell < 1 ? 'L' : ''
          );

          $this -> pdf -> SetXY(
            $this -> pdf -> GetX(), $this -> pdf -> GetY() + $margins[ 'y' ]
          );

          $align = 'L';
          if( $irow == 0 )
             $align = 'C';
          elseif( is_array( $tableOptions[ $icell ] ) && isset( $tableOptions[ $icell ][ 'align' ] ))
            if( $tableOptions[ $icell ][ 'align' ] == 'center' )
              $align = 'C';
            elseif( $tableOptions[ $icell ][ 'align' ] == 'right' )
              $align = 'R';

          // Komórka z wartością pola.
          $this -> pdf -> Cell(
            $widths[ $icell ], $cellHeight, $cell, 0, 0, $align
          );

          $this -> pdf -> SetXY(
            $this -> pdf -> GetX(), $this -> pdf -> GetY() - $margins[ 'y' ]
          );

          // Margines prawy
          $this -> pdf -> Cell(
            $margins[ 'x' ], $sepHeight, '', 'R'
          );

        }

        $this -> pdf -> Ln();
  
      }
      
      $this -> pdf -> SetXY(
        $this -> pdf -> GetX() + $tableMargin, $this -> pdf -> getY()
      );
      $this -> pdf -> Cell(
        $tableWidth, 0, '', 'T'
      );
      
      $this -> dump( 'draw services completed -> OK!' );
      
      return $this;
    
    }
    
    /**
     * Rysowanie tabeli z cenami netto i brutto oraz podsumowaniem.
     * @param array $options Opcję tabeli cen.
     * @return InvoiceGenerator
     */
    protected function drawPrices( $options = array()) {
    
      $options = $this -> prepareOptions(
        $options, array( 'fontSize' => 10 )
      );

      $this -> dump( 'draw: prices -> OK!' );

      $table = array_merge(
        array(
          array( 'Wg stawki VAT', 'Obrót netto', 'Kwota VAT', 'Obrót brutto' )
        ), $this -> invoice -> getPrices()
      );
      
      $half = ( $this -> sheet[ 'width' ] - 2 * $this -> sheet[ 'margin' ][ 'left' ] ) * .5;
      $leftMargin = $this -> sheet[ 'margin' ][ 'left' ] + $half;
      $cellWidth = $half / count( $table[ 0 ]);
      $cellHeight = ( $options[ 'fontSize' ] + 10 ) * $this -> sheet[ 'unit' ];

      if( $this -> checkPagebreak( sizeof( $table ) * $cellHeight ))
        $this -> pageBreak();

      $this -> pdf -> SetX( $leftMargin );
      $this -> pdf -> SetFontSize( $options[ 'fontSize' ]);

      /**
       * Iteracja po tabeli i jej rysowanie.
       */
      foreach( $table as $irow => $row ) {
      
        foreach( $row as $icell => $cell ) {
        
          if( in_array( $irow, array( 0, count( $table ) - 1 )))
            $this -> pdf -> SetFont( 'arialpl', 'B' );
          else
            $this -> pdf -> SetFont( 'arialpl', '' );
      
          list( $cell ) = $this -> prepareString(
            $irow > 0 && $icell > 0 ? $this -> makePrice( $cell ) : $cell, $options[ 'fontSize' ]
          );
          
          $this -> pdf -> Cell(
            $cellWidth, $cellHeight, $cell, 0, 0, 'R'
          );
          
        }
        
        $this -> pdf -> Ln();
        $this -> pdf -> SetX( $leftMargin );
        
        if( $irow == 0 ) 
          $this -> pdf -> Cell(
            $half, 1, '', 'B', 2
          );
        
      }
      
      $this -> dump( 'draw prices completed -> OK!' );
      
      return $this;
    
    }

    /**
     * Drukuje dodatkowe informacje o płatności.
     * @param array $options Opcję informacji o płatności.
     * @return InvoiceGenerator
     */
    protected function drawInfo( $options = array()) {
    
      $options = $this -> prepareOptions(
        $options, array( 'fontSize' => 10, 'interline' => 1.4 )
      );
    
      $this -> dump( 'draw: prices info -> OK!' );
    
      $cellHeight = $options[ 'fontSize' ] * $this -> sheet[ 'unit'] * $options[ 'interline' ];
    
      require_once 'number2string/number_to_string_class.php';
    
      try {
        
        $toPay = $this -> invoice -> getPaymentSum();
      
        $t = array();
        
        list( $t[ 0 ] ) = $this -> prepareString(
          'Razem do zapłaty: ' . $this -> makePrice( $toPay ) . ' PLN', $options[ 'fontSize' ]
        );
      
        list( $t[ 1 ] ) = $this -> prepareString(
          'Słownie: ' . NumberToString::parseNumber( $toPay ), $options[ 'fontSize' ]
        );
      
        list( $t[ 2 ] ) = $this -> prepareString(
          'Termin płatności: ' . $this -> invoice -> getPaymentDate(), $options[ 'fontSize' ]
        );
      
        list( $t[ 3 ] ) = $this -> prepareString(
          'Forma płatności: przelewem na rachunek bankowy nr ' . $this -> invoice -> seller -> bank_account, $options[ 'fontSize' ]
        );
      
        if( $this -> checkPagebreak( 5 * $cellHeight ))
          $this -> pageBreak();
      
        for( $i = 0; $i < 4; ++$i ) {
        
          if( $i % 2 == 0 )
            $this -> pdf -> SetFont( 'arialpl', 'B' );
          else
            $this -> pdf -> SetFont( 'arialpl', '' );
        
          if( $i == 2 )
            $this -> pdf -> Cell(
              0, $cellHeight, '', 0, 1
            );
        
          $this -> pdf -> Cell(
            0, $cellHeight, $t[ $i ], 0, 1
          );
          
        }
      
      } catch( InvalidArgumentException $e ) {
      
        $this -> dump( $e -> getMessage());
        exit;
        
      }
      
      $this -> dump( 'draw prices info completed -> OK!' );
    
      return $this;
    
    }
    
    /**
     * Rysuję podpis wystawiającego fakture.
     * @param array $options Opcję wejściowe.
     * @return InvoiceGenerator
     */
    private function drawSign( $options = array()) {
    
      $options = $this -> prepareOptions(
        $options, array( 'fontSizes' => array( 'sign' => 12, 'undertitle' => 8 ), 'height' => 30 )
      );

      $signHeight = $options[ 'height' ] * $this -> sheet[ 'unit' ];    
      if( $this -> checkPagebreak( $signHeight ))
        $this -> pageBreak();
        
      $width = .4 * ( $this -> sheet[ 'width' ] - 2 * $this -> sheet[ 'margin' ][ 'left' ] );
      $marginX = $this -> sheet[ 'margin' ][ 'left' ] + $width;
      
      $this -> pdf -> SetX( $marginX );
      $this -> pdf -> SetFont( 'arialpl', 'I', $options[ 'fontSizes' ][ 'sign' ] );
      
      list( $sign, $tmp, $height ) = $this -> prepareString(
        $this -> invoice -> seller -> sign, $options[ 'fontSizes' ][ 'sign' ]
      );

      // Podpis + odstep + podkreślenie
      $this -> pdf -> Cell(
        $width, $height, $sign, 0, 2, 'C'
      );
      $this -> pdf -> SetXY(
        $marginX, $this -> pdf -> GetY() + 5 * $this -> sheet[ 'unit' ]
      ); 
      $this -> pdf -> Cell(
        $width, 0, '', 'B'
      );
      
      $this -> pdf -> SetXY(
        $marginX, $this -> pdf -> GetY() + 5 * $this -> sheet[ 'unit' ]
      ); 
      
      $this -> pdf -> SetFont( 'arialpl', '', $options[ 'fontSizes' ][ 'undertitle' ] );
      list( $note, $tmp, $height ) = $this -> prepareString(
        'wystawił', $options[ 'fontSizes' ][ 'undertitle' ]
      );
      
      $this -> pdf -> Cell(
        $width, $height, $note, 0, 2, 'C'
      );
    
      return $this;
    
    }

    /**
     * Rysuję stopkę. (najczęściej używany podczas zmiany strony)
     * @param array $options Opcję wejściowe stopki.
     * @return InvoiceGenerator
     */
    protected function drawFooter( $options = array()) {
    
      $options = array_merge(
        array(
          'fontSizes' => array(
            'info' => 8, 'page' => 8
          )
        ), $options
      );
      
      $this -> dump( 'draw: footer -> OK!' );
      
      $half = ( $this -> sheet[ 'width' ] - 2 * $this -> sheet[ 'margin' ][ 'left' ] ) * .5;
      
      $info = $this -> invoice -> seller -> name . "\nt. " .
        $this -> invoice -> seller -> phone . ', f. ' .
        $this -> invoice -> seller -> fax;

      list( $info, $width, $height ) = $this -> prepareString(
        $info, $options[ 'fontSizes' ][ 'info' ]
      );
      $height += 5 * $this -> sheet[ 'unit' ];

      $this -> pdf -> SetXY(
        $this -> sheet[ 'margin' ][ 'left' ],
        $this -> sheet[ 'margin' ][ 'bottom' ]
      );
                
      $this -> pdf -> SetFont( 'arialpl' );
      $this -> pdf -> MultiCell( $half, $height, $info, 0, 'L' );

      $page = 'Strona ' . $this -> page . ' z {nb}';
      list( $page, $width, $height ) = $this -> prepareString(
        $page, $options[ 'fontSizes' ][ 'page' ]
      );
      
      $this -> pdf -> SetXY(
        $this -> sheet[ 'margin' ][ 'left' ] + $half,
        $this -> sheet[ 'margin' ][ 'bottom' ] + 2 * $height
      );
      
      $this -> pdf -> Cell(
        $half, $height, $page, 0, 0, 'R'
      );
      
      $this -> dump( 'draw footer completed -> OK!' );
    
      return $this;
    
    }

    /**
     * Inicjalizacja podstawowych danych.
     * @param array $options Opcję inicjalizacji.
     */
    protected function init( $options = array()) {
    
      $options = $this -> prepareOptions(
        $options, array(
          'footer' => array(
            'fontSize' => 8, 'lines' => 2
          ), 'margin' => array(
            'x' => .05, 'y' => .05
          )
        )
      );

      // Import czcionek do pdfa.
      $this -> pdf -> AddFont( 'arialpl', '', 'arialpl.php' );
      $this -> pdf -> AddFont( 'arialpl', 'I', 'arialpli.php' );
      $this -> pdf -> AddFont( 'arialpl', 'B', 'arialplb.php' );

      $this -> pdf -> SetFont( 'arialpl', '', 12 );

      // Konfiguracja marginesów.
      $this -> sheet[ 'margin' ][ 'left' ] = $this -> sheet[ 'margin' ][ 'right' ] =
        $this -> sheet[ 'width' ] * $options[ 'margin' ][ 'x' ];
        
      $this -> sheet[ 'margin' ][ 'top' ] = 
        $this -> sheet[ 'height' ] * $options[ 'margin' ][ 'y' ];
        
      $this -> sheet[ 'margin' ][ 'bottom' ] =
        $this -> sheet[ 'height' ] - $this -> sheet[ 'margin' ][ 'top' ]; 
      $this -> sheet[ 'margin' ][ 'bottom' ] -=
        $options[ 'footer' ][ 'lines' ] * $options[ 'footer' ][ 'fontSize' ] * $this -> sheet[ 'unit' ];
      
      $this -> pdf -> SetMargins(
        $this -> sheet[ 'margin' ][ 'left' ], $this -> sheet[ 'margin' ][ 'top' ]
      );

      $this -> pdf -> SetAutoPageBreak( false );
      $this -> pdf -> SetDisplayMode( 'real' );
      $this -> pdf -> AliasNbPages();
      $this -> pdf -> AddPage();
      
      $this -> pdf -> SetXY(
        $this -> sheet[ 'margin' ][ 'left' ],
        $this -> sheet[ 'margin' ][ 'top' ]
      );
    
    }
    
    /**
     * Callback do sprawdzania poprawności modelu - domyślnie poprawny.
     * @param mixed $m Instancja modelu
     * @return boolean
     */
    protected function checkModel( $m ) {
    
      return true;
    
    }
    
    /**
     * Rysuje separator o określonej wysokości.
     * @param float $height Wysokość separatora. 
     * @return InvoiceGenerator
     */
    final protected function drawSeparator( $height = 20 ) {
    
      if( $this -> checkPagebreak( $height ))
        $this -> pageBreak();
      
      else
        $this -> pdf -> SetXY(
          $this -> sheet[ 'margin' ][ 'left' ],
          $this -> pdf -> GetY() + $this -> sheet[ 'unit' ] * $height
        );
        
      return $this;
    
    }

    /**
     * Informuję o tym, czy element o danej wysokości złamię stronę.
     * @param float $elHeight Wysokość elementu.
     * @return boolean
     */
    final protected function checkPagebreak( $elHeight ) {
    
      return $this -> pdf -> GetY() + $elHeight > $this -> sheet[ 'margin' ][ 'bottom' ];
    
    }
    
    /**
     * Funkcja rysująca stopkę i rozpoczyna nową stronę.
     * @return InvoiceGenerator
     */
    final protected function pageBreak() {
    
      $this -> drawFooter() -> pdf -> AddPage();
      ++$this -> page;
      return $this;
    
    }
    
    /**
     * Tworzy string zgodny ze standardem odpowiednim pdfowi.
     * @param string $s Przetwarzany łańcuch.
     * @param int $fontSize Wielkość czcionki.
     * @param array $color Kolor w postaci trzech wartości dec.
     * @return array
     */
    protected function prepareString( $s, $fontSize, $color = array( 0, 0, 0 )) {

      $this -> pdf -> SetFontSize( $fontSize );
      
      if( mb_detect_encoding( $s ) == 'UTF-8' )
        $s = iconv( 'UTF-8', 'ISO-8859-2', $s );
      
      $width = $this -> pdf -> GetStringWidth( $s );
      $height = $fontSize * $this -> sheet[ 'unit' ];

      if( is_array( $color ) && count( $color ) == 3 )
        $this -> pdf -> SetTextColor( $color[ 0 ], $color[ 1 ], $color[ 2 ] );

      return array( $s, $width, $height );

    }
    
    /**
     * Przetwarza opcję wejściowe i domyślne, a następnie zwraca ostateczną wersję opcji.
     * @param array $options Opcję wejściowe.
     * @param array $init Opcję domyślne.
     * @return array
     */
    protected function prepareOptions( $options, $init = array()) {
    
      if( !is_array( $init ))
        $init = array( $init );
    
      if( is_array( $options ))
        $options = array_merge(
          $init, $options
        );
      else
        $options = $init;
    
      return $options;
    
    }
    
    /**
     * Robi z danego floata wartość z określoną ilością miejsc po przecinku.
     * @param float $v Wprowadzana wartość.
     * @param int $precission Precyzja danej wartości do zwrócenia.
     * @return string
     */
    final protected function preparePoint( $v, $precission = 0 ) {

      $integer = $result = floor( abs( $v ));
      if( $precission > 0 ) {
  
        $fraction = (string) round( round( $v - $integer, $precission ) * pow( 10, $precission ), 0);
        $result .= ',' . $fraction;
        
        for( $i = strlen( $fraction ); $i < $precission; ++$i )
          $result .= '0';

      }

      return $result;

    }
    
    /**
     * Przetwarza wartość na poprawną cenę.
     * @param miexd $v Dana wartość.
     * @return string
     */
    final protected function makePrice( $v ) {

      $v = $this -> preparePoint( $v, 2 );

      if( strlen( $v ) < 7 )
        return $v;

      $result = substr( $v, strlen( $v ) - 6, 6 );
      for( $i = strlen( $v ) - 7; $i >= 0; --$i ) {
        
        $numbersPart = substr( $v, $i, 1 );
        $addDot = $numbersPart . (( strlen( $v ) - 7 - $i ) % 3 == 0 ? '.' : '' );
        $result = $addDot . $result;
        
      }

      return $result;

    }
    
    /**
     * Dump informacji dla trybu logowanego.
     * @param string $t Komunikat.
     */
    protected function dump( $t ) {
    
      if( !$this -> silent )
        print $t . "\n";

    }

  }
