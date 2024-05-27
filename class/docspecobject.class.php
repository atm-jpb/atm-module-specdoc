<?php 
/**
 * Le rôle de cette classe est de recuperer les fichiers md contenu dans la racine du dossier des modules custom
 * 
 * De les parser et de les injecter dans à la demande  au frontal 
 * 
 */
require_once DOL_DOCUMENT_ROOT .'/includes/parsedown/Parsedown.php';


class DocSpecObject {

   public $Telement;

   const    UP_ONE_DIRECTORY ='../';
   const    RESOURCE_TYPE_SPECS='specs.md';
   const    RESOURCE_TYPE_DOCS='docs.md';

   /** regex patterns definition  */
   // Element begin with [context-  et finissant par  ]
   const    PATTERN_CONTEXT = "/\[context-(.*?)\]/";
   // Element begin with [need-  et finissant par  ]
   const    PATTERN_NEED = "\[need-(.*?)\]";

   const    PATTERN_CONTEXT_NEED = "/\[context-(.*?)\]\[need-(.*?)\]/";
   const    PATTERN_LINE_DESCRIPTOR = "/\[context-(.*?)\]\[need-(.*?)\]/";
   public   $nameModule;
   private  $TContextLines;
   public   $parser; 


   //------Multi context  need  date et du texte --------
   //const GLOBAL_REGEX = '/((\[context-[^\]]+\])+)\[need-\d{2}\](\[date-\d{4}\/\d{2}\/\d{2}\])?\s*\n(.*)/m';
   //const GLOBAL_REGEX = '/((\[context-[^\]]+\])+)(\[need-\d{2}\])(\[date-\d{4}\/\d{2}\/\d{2}\])?\s*\n(.*(?:\n[^\[\]]*)*)/m';
   const GLOBAL_REGEX       ='/((\[context-[^\]]+\])+)(\[need-\d{2}\])(\[date-\d{4}\/\d{2}\/\d{2}\])?\s*\n((?:(?!\[context|\[need|\[date).)*)/s';
   const DESCRIPTION_REGEX  = '/\[(?!context|need|date)[^\]]+\]\s*(.*)/'; 
   const CONTEXT_REGEX      ='/\[context-[^\]]+\]/';
    
   const PREFIX_CONTEXT =   '/\[context-([^\]]+)\]/';
   const PREFIX_DATE =      '/\[date-([^\]]+)\]/';
   const PREFIX_NEED =      '/\[need-([^\]]+)\]/';


   public function __construct($nameModule){
       $this->nameModule = $nameModule;
       $this->parser =  new Parsedown();
       $this->TContextLines = [];
   }
   

    /**
     * @param LineDocSpec $line
     */
   public function addTContextLine($line){
    $this->TContextLines[] = $line;
   }

    /**
     * Récuperation en fonction du context courant  pour le fichier de ressource traité
     */
    public function loadResourcesFile($typeMarkdowFile, $globalContext){
        
       // Récuperation du chemin vers le fichier source
        $urlToFile =  $this->getPathTofile($typeMarkdowFile); 
        if (file_exists($urlToFile)) {

            dol_syslog('Found resources ' . $typeMarkdowFile . ' for <strong>'. $this->nameModule . '</strong> <br>',LOG_NOTICE);
            $file_handle = fopen( $urlToFile, 'r') or die("Unable to open file!");;
            
            $lineSpec = $this->addLinesSpec($file_handle, $globalContext);

            // insertion du dernier objet
            $this->addTContextLine($lineSpec);
            fclose($file_handle);
        }else{
            dol_syslog('no resources ' . $typeMarkdowFile . ' for <strong>'. $this->nameModule . '</strong> <br>', LOG_NOTICE);
        }    
      return $this->TContextLines;
    }


    private function getPathToFile($typeMarkdowFile){
        return $this::UP_ONE_DIRECTORY . $this->nameModule . '/'.$typeMarkdowFile;
    }

    /**
     * 
     */
    private function addLinesSpec($file_handle, $globalContext){

        $lineSpec = new LineDocSpec();
        $currentContext = "";
       
        foreach ($this->get_all_lines($file_handle) as $TextLine) {
            /** @todo parsing de la ligne pour récupération des clés [context] et [need] */
            if (!empty($TextLine)){

                $globalRegex = '/\[context-([^\]]+)\]\[need-(\d+)\]\[date-(\d{4}\/\d{2}\/\d{2})\]\s*(.*?)((?=\[context)|$)/s';
                $descriptionRegex = '/\[(?!context|need|date)([^\]]+)\]\s*(.*)/s';
                $contextRegex = '/\[context-([^\]]+)\]/';
                $dateRegex = '/\[date-(\d{4}\/\d{2}\/\d{2})\]/';
                $needRegex = '/\[need-(\d+)\]/';
               

                preg_match_all($globalRegex, $TextLine, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    preg_match($contextRegex, $match[0], $contextMatch);
                    $context = $contextMatch[1];

                    preg_match($dateRegex, $match[0], $dateMatch);
                    $date = $dateMatch[1];

                    preg_match($needRegex, $match[0], $needMatch);
                    $need = $needMatch[1];

                    preg_match($descriptionRegex, $match[0], $descriptionMatch);
                    $description = trim($descriptionMatch[0]);
                
                    echo "Contexte: $context\n";
                    echo "Date: $date\n";
                    echo "Besoin: $need\n";
                    echo "Description: " . var_export($descriptionMatch, true) . "\n\n";
                }

                /*
                preg_match_all(SELF::GLOBAL_REGEX, $TextLine, $matches,PREG_SET_ORDER);
                
                if (!empty($matches[1])) echo '<pre>' . var_export($matches,true) .'</pre>'; 
                $contexts = $matches[1];
                $need = $matches[3];
                $dates = $matches[4];

                // -- cas particulier je n'arrive pas à isoler via la regex la description 
                // je passe par une sous regex pour le recuperer 
                preg_match(SELF::DESCRIPTION_REGEX, $TextLine, $matches2);
                if (!empty($matches[1])) echo '<pre>' . var_export($matches2,true) .'</pre>'; 
                $description = $matches2[1];
                
              
                // Array to store individual context tags
                $contextArray = [];

               

                foreach ($contexts as $contextGroup) {
                    preg_match_all(SELF::CONTEXT_REGEX, $contextGroup, $contextMatches);
                    foreach ($contextMatches[0] as $contextTag) {
                        $contextArray[] = $this->extractContextPart($contextTag, self::PREFIX_CONTEXT);
                    }
                }
             
                //var_dump($globalContext);
               //if (!empty($contextArray)) echo var_export( $contextArray ,true);
                if ( !empty($contextArray) && in_array($globalContext, $contextArray )){
                   // echo var_export( $contextArray ,true);
                    
                   //     echo "Contexts:\n";
                       
                   
                } 

**/
                
                
                //echo "\nDates:\n";
               // print_r($dates);
              //  echo "\nDescriptions:\n";
              //  print_r($descriptions);

                // context déclaré dans la ligne
                //preg_match($this::PATTERN_CONTEXT, $TextLine, $matches);
                 
                 /** @todo  voir pôur l'affichage de tous les context du module dans la page admin. on doit shinter ce test  ( || administration )   */ 
               
                 // est ce que j'ai une [context-xxx] pour cette ligne ? 
               // if (key_exists(1, $matches)){
               //     print '<pre>' . var_export($matches,true) . '</pre>';   
               //   $currentContext = $matches[1];      
                  // est ce que le context de ligne est egal au context global
                  /** @todo  si j'en ai plusieurs de déclaré  je dois le gérer **/
              //     if ($globalContext == $currentContext ){ 
               //         $res = preg_match_all($this::PATTERN_LINE_DESCRIPTOR, $TextLine);
                //        if ( $res) { 
                 //           if  (!empty($lineSpec->getContext())){
                                // stockage ligne courante et reset de l'object LineDocSpec
                 //               $this->addTContextLine($lineSpec);
                 //               $lineSpec = new LineDocSpec();
                 //           }
                            // recupération de la description sans les tags context et need
                 //           $mDesc =  preg_split($this::PATTERN_LINE_DESCRIPTOR, $TextLine) ; 
                 //           $desc = array_key_exists(1, $mDesc) ? str_replace( "\\" , " ", $mDesc[1] )   :  "" ;
                 //           /** @todo insertion bonnes valeurs */    
                 //           $lineSpec->setContext($globalContext)
                 //                    ->setDesc($this->parser->text($desc))
                 //                    ->setDate('2024-03-18')
                 //                    ->setNeed('14');
                 //       } 
                 //   }  
               // }elseif  ( $globalContext == $currentContext ){
               //     $lineSpec->setSubLine($this->parser->text($TextLine));    
              //  }     
            }
        }
        // last
        return $lineSpec;
    }

    // Fonction pour extraire la partie droite du tag context
    public function extractContextPart($tag, $prefix = "") {
    $regex = $prefix;
    $extractedPart = '';
    if (preg_match($regex, $tag, $matches)) {
        $extractedPart = $matches[1];
    }
    return $extractedPart;
    }


    /**
     * 
     */
    private function get_all_lines($file_handle) { 
        while (!feof($file_handle)) {
            yield fgets($file_handle);
        }
    }
    /**
     * Magic method call from child
     */
    public function __toString()
    { 
        // Obtient le dossier parent du dossier contenant le fichier de la classe appelante
        
        return ucfirst( basename(dirname(dirname((new ReflectionClass($this))->getFileName()))) );
    } 
    
    public function __get($prop){
        echo 'Propriété ' .$prop. ' inaccessible.<br>';
    }
   
   
    public function __set($prop, $valeur){
        echo 'Impossible de mettre à jour la valeur de ' .$prop. ' avec "'
        .$valeur. '" (propriété inaccessible)';
    }

}


/**
 * object 
 */
class LineDocSpec {
    
    private $context;
    private $need;
    private  $date;
    private $desc;
    private $subLine = [];


    public function setContext(string $context): LineDocSpec{
        $this->context = $context;
        return $this;
    }

    public function setNeed(string $need): LineDocSpec{
        $this->need = $need;
        return $this;
    }

    public function setDate(string $date): LineDocSpec{
        $this->date = $date;
        return $this;
    }

    public function setDesc(string $desc) : LineDocSpec{
        $this->desc = $desc;
        return $this;
    }

    public function setSubLine(string  $val) : LineDocSpec{
        $this->subLine[] = $val;
        return $this;
    }


    public function getSubLine(): array {
        return  $this->subLine;
    }

    public function getSubLineElement($index): string|null {
        return  $this->subLine[$index];
    }
    public function getContext():string|null{
        return $this->context;
    }
    public function getNeed():string|null{
        return $this->need;
    }
    public function getDate():string|null{
        return $this->date;
    }
    public function getDesc():string|null{
        return $this->desc;
    }



}