<?php

  /**
   * Klasa przekształcająca liczbę zmiennoprzecinkową na jej
   * odpowiednik tekstowy w danym języku.
   *
   * @author Marcin Czołnowski
   * @license http://creativecommons.org/licenses/by-nd/3.0/deed.en
   * @version v0.1
   * @example ./numbers Example of use
   */
  class NumberToString {
  
    /**
     * Język do którego ma zostać przekształcona liczba.
     * @var string
     */
    public $language = 'pol';

    /**
     * Wartość która podlega przekształceniu.
     * @var float
     */
    protected $number = 0x0;
  
    /**
     * Translacja metod na funkcję klasy.
     * @var array
     */
    protected $methods = array(
      NumberToString::Words => 'makeWords',
      NumberToString::Numbers => 'makeNumbers'
    );
  
    /**
     * Tablica translacji.
     * @var array
     */
    protected $trans = array(
      'pol' => array(
        'unity' => array(
          'Zero', 'Jeden', 'Dwa', 'Trzy', 'Cztery', 'Pięć', 'Sześć',
          'Siedem', 'Osiem', 'Dziewięć', 'Dziesięć', 'Jedenaście',
          'Dwanaście', 'Trzynaście', 'Czternaście', 'Piętnaście',
          'Szesnaście', 'Siedemnaście', 'Osiemnaście', 'Dziewiętnaście'
        ), 'tens' => array(
          'Dwadzieścia', 'Trzydzieści', 'Czterdzieści', 'Pięćdziesiąt',
          'Sześćdziesiąt', 'Siedemdziesiąt', 'Osiemdziesiąt',
          'Dziewięćdziesiąt'
        ), 'hundreds' => array(
          null, 'Sto', 'Dwieście', 'Trzysta', 'Czterysta', 'Pięćset',
          'Sześćset', 'Siedemset', 'Osiemset', 'Dziewięćset'
        ), 'other' => array(
          array( 'Tysiąc', 'Tysiące', 'Tysięcy'     ),
          array( 'Milion', 'Miliony', 'Milionów'    ),
          array( 'Miliard', 'Miliardy', 'Miliardów' )
        )
      )
    );
    
    /**
     * Rzutowanie do postaci liczbowej (number/precission*base)
     * @var const int
     */
    const Numbers = 2;
    
    /**
     * Rzutowanie do postaci słownej.
     * @var const int
     */
    const Words = 4;
  
    /**
     * Konstruktor przyjmuję argumenty początkowe.
     * @param mixed $number Liczba która będzie tłumaczona przez klasę.
     * @param string $language Język na który ma zostać przetłumaczona liczba.
     * @throws InvalidArgumentException
     */
    final public function __construct( $number, $language = null ) {
    
      if( is_numeric( $number ) && !is_float( $number ))
        $number = (float) $number;
      elseif( !is_numeric( $number ))
        throw new InvalidArgumentException( 'Invalid number format!' );

      if( !is_null( $language ))
        if( !is_string( $language ))
          throw new InvalidArgumentException( 'Invalid language format!' );
        elseif( !isset( $this -> trans[ $language ]))
          throw new InvalidArgumentException( 'Invalid translation language!' );
   
      $this -> number = $number;
      
      if( !is_null( $language ))
        $this -> language = $language;
   
    }   
    
    /**
     * Statyczna metoda parsująca. Przyjmuje parametry konstruktora i metody parse.
     * @param mixed $number Liczba która będzie tłumaczona przez klasę.
     * @param string $between Separator.
     * @param int $precission Precyzja zmiennoprzecinkowa.
     * @param int $integer_trans Metoda przetwarzania części całkowitej.
     * @param int $fraction_trans Metoda przetwarzania części ułamkowej.
     * @return string
     * @throws InvalidArgumentException
     */
    public static function parseNumber( $number, $between = ' ', $precission = 2, $integer_trans = NumberToString::Words, $fraction_trans = NumberToString::Numbers ) {
    
      $instance = new NumberToString( $number );
      return $instance -> parse(
        $between, $precission, $integer_trans, $fraction_trans
      );
    
    }
    
    /**
     * Przetwarza daną dla instancji liczbę według reguł.
     * @param string $between Separator.
     * @param int $precission Precyzja zmiennoprzecinkowa.
     * @param int $integer_trans Metoda przetwarzania części całkowitej.
     * @param int $fraction_trans Metoda przetwarzania części ułamkowej.
     * @return string
     * @throws InvalidArgumentException
     */
    public function parse( $between = ' ', $precission = 2, $integer_trans = NumberToString::Words, $fraction_trans = NumberToString::Numbers ) {
    
      if( !is_string( $between ))
        $between = ' ';
    
      if( !is_numeric( $precission ))
        throw new InvalidArgumentException( 'Invalid precission format!' );
      elseif( !is_int( $precission ))
        $precission = (int) $precission;
      
      if( $precission < 1 )
        $precission = 0;
      return
        strtolower( $this -> getInteger( $integer_trans )) . $between . 
        strtolower( $this -> getFraction( $fraction_trans, $precission ));
    
    }
    
    /**
     * Metoda zwracająca część całkowitą przetworzoną według metody.
     * @param int $integer_trans Metoda przetwarzania.
     * @return string
     */
    final protected function getInteger( $integer_trans ) {
      
      if( !isset( $this -> methods[ $integer_trans ]))
        throw new RuntimeException( 'Invalid translation method!' );
      
      $number = $this -> convertInteger();
      return $this -> { $this -> methods[ $integer_trans ]}( $number, strlen((string) $number ));
    
    }
    
    /**
     * Polimorficzna metoda wyłuskująca część całkowitą z liczby.
     * @return int
     */
    protected function convertInteger() {
    
      return (int) floor( $this -> number );
    
    }
    
    /**
     * Metoda zwracająca część ułamkową przetworzoną według metody i z podaną precyzją.
     * @param int $fraction_trans Metoda przetwarzania.
     * @param int $precission Precyzja.
     * @return string
     */
    final protected function getFraction( $fraction_trans, $precission ) {
      
      if( !isset( $this -> methods[ $fraction_trans ]))
        throw new RuntimeException( 'Invalid translation method!' );
      
      $number = $this -> convertFraction( $precission );
      return $this -> { $this -> methods[ $fraction_trans ]}( $number, $precission );
      
    }
    
    /**
     * Polimorficzna metoda wyłuskująca część ułamkową z liczby.
     * @param int $precission Precyzja z jaką ma zostać przedstawiona część ułamkowa.
     * @return int
     */
    protected function convertFraction( $precission ) {
    
      return round(
        $this -> number - floor( $this -> number ), $precission
      ) * pow( 10, $precission );
    
    }
    
    /**
     * Przetwarza daną liczbę na wartość słowną w wybranym języku.
     * @param int $number Dana liczba.
     * @return string
     */
    private function makeWords( $number ) {

      if( $number < 1 )
        return $this -> trans[ $this -> language ][ 'unity' ][ 0 ];
    
      $words = null;
      $small = $this -> nextPhrase( $words, $number, 0 );

      // Liczba mniejsza niż 10^3 - przerabianie resztek.
      if( $small > 0 )
        $this -> makeSmall( $words, $small );

      return rtrim( $words );
    
    }
    
    /**
     * Interpretuję kolejny tysiąckrotny człon liczby.
     * @param &string $words Treść słowna.
     * @param int $number Dana część liczby do interpretacji.
     * @param int $level Poziom zagnieżdzenia. (tysięczne, milionowe, miliardowe, ...)
     * @return int
     */
    private function nextPhrase( &$words, $number, $level ) {
    
      $small = 0;
      $divisor = pow( 1000, $level + 1 );
    
      // Liczba większa niż level-ta wielokrotność tysiąca.
      if( $number >= $divisor ) {
        
        $number = $this -> nextPhrase( $words, $number, $level + 1 );
        $small = (int) ( $number / $divisor );
        
        if( $small > 1 )
          $this -> makeSmall( $words, $small );
        
        $this -> makeBig( $words, $small, $level );
        
      }
    
      return $number - $small * $divisor;
    
    }
  
    /**
     * Interpretuję i konwertuję liczbę mniejszą od tysiąca.
     * @param &string $words Treść słowna.
     * @param int $number Dana część liczby do interpretacji.
     */
    private function makeSmall( &$words, $number ) {
    
      if( $number < 1 )
        return;
    
      $t = $this -> trans[ $this -> language ];
      
      // Konwersja setek.
      $h = (int) ( $number / 100 );
      if( $h > 0 ) {
        
        $words .= $t[ 'hundreds' ][ $h ] . ' ';
        $number -= $h * 100;
        
      }
      
      // Konwersja dziesiątek i jedności.
      if( $number > 0 ) {

        if( $number < 20 )
          $words .= $t[ 'unity' ][ $number ] . ' ';
          
        else {
        
          $words .= $t[ 'tens' ][ ( $number / 10 ) - 2 ] . ' ';
          if( $number % 10 > 0 )
            $words .= $t[ 'unity' ][ $number % 10 ] . ' ';
        
        }
        
      }
      
    }
  
    /**
     * Interpretuję określenie poziomu liczby (tysiąc, milion, ...) oraz jego odmianę.
     * @param &string $words Treść słowna.
     * @param int $number Dana część liczby do interpretacji.
     * @param int $level Poziom zagnieżdzenia. (tysięczne, milionowe, miliardowe, ...)
     */
    private function makeBig( &$words, $number, $level ) {
    
      $index = 2;
      if( $number == 1 )
        $index = 0;
      elseif( in_array( $number % 10, array( 2, 3, 4 )))
        $index = 1;
    
     /* $l = $number % 10;
      $index = $l == 1 ? 0 : 1;
      
      if( $l > 4 || ( $number >= 10 && ( $number <= 20 || $l == 0 )))
        $index = 2;*/

      $words .= $this -> trans[ $this -> language ][ 'other' ][ $level ][ $index ] . ' ';
    
    }
  
    /**
     * Przetwarza daną liczbę na wartość liczba/dopełnienie_do_10^n (n-długość ciągu liczby)
     * @param int $number Dana liczba.
     * @param int $precission Precyzja zmiennoprzecinkowa.
     * @return string
     */
    private function makeNumbers( $number, $precission ) {
    
      $number = (string) $number;
    
      for( $i = $precission; $i > strlen( $number ); --$i )
        $number = '0' . $number;
    
      return $number . '/' . pow( 10, $precission );
    
    }
  
  }
