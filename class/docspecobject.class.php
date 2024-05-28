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
   /* 
   const GLOBAL_REGEX       ='/((\[context-[^\]]+\])+)(\[need-\d{2}\])(\[date-\d{4}\/\d{2}\/\d{2}\])?\s*\n((?:(?!\[context|\[need|\[date).)*)/s';
   const DESCRIPTION_REGEX  = '/\[(?!context|need|date)[^\]]+\]\s*(.*)/'; 
   const CONTEXT_REGEX      ='/\[context-[^\]]+\]/';
    
   const PREFIX_CONTEXT =   '/\[context-([^\]]+)\]/';
   const PREFIX_DATE =      '/\[date-([^\]]+)\]/';
   const PREFIX_NEED =      '/\[need-([^\]]+)\]/';
**/
   const GLOBAL_REGEX = '/(\[\[context-[^\]]+\]\])+\[\[need-(\d+)\]\]\[\[date-(\d{4}\/\d{2}\/\d{2})\]\]\s*(.*?)(?=\[\[context|\z)/s';
   const CONTEXT_REGEX = '/\[\[context-([^\]]+)\]\]/';
   const NEED_REGEX = '/\[\[need-(\d+)\]\]/';
   const DATE_REGEX = '/\[\[date-(\d{4}\/\d{2}\/\d{2})\]\]/';
   const DESCRIPTION_REGEX = '/\]\]\s*(.*?)(?=\[\[context|\z)/s';


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
     * travail depuis un fichier pour stockage dans l'objet
     */
    private function addLinesSpec($file_handle, $globalContext){

        $lineSpec = new LineDocSpec();
        $currentContext = "";
        $description_buffer = "";
        $in_description = false;
        foreach ($this->get_all_lines($file_handle) as $TextLine) {
            
            if (!empty($TextLine)){
                if (preg_match(self::GLOBAL_REGEX, $TextLine, $matches)) {
                    if ($in_description) {
                        //echo "Description: $description_buffer\n\n";
                        $description_buffer = "";
                        $in_description = false;
                    }
    
                    $contexts = [];
                    preg_match_all(self::CONTEXT_REGEX, $matches[0], $contextMatches);
                    foreach ($contextMatches[1] as $context) {
                        $contexts[] = $context;
                    }

                    if ( !empty($contexts) && in_array($globalContext, $contexts )){
                         echo var_export( $contexts ,true);
                    }

                    preg_match(self::NEED_REGEX, $matches[0], $needMatch);
                    $need = $needMatch[1];
    
                    preg_match(self::DATE_REGEX, $matches[0], $dateMatch);
                    $date = $dateMatch[1];
    
                    preg_match(self::DESCRIPTION_REGEX, $matches[0], $descriptionMatch);
                    $description = $descriptionMatch[1];


                    foreach ($contexts as $key => $currentContext) {
                        # code...
                        if ($globalContext == $currentContext ){ 
                            $res = preg_match_all($this::PATTERN_LINE_DESCRIPTOR, $TextLine);
                            if ( $res) { 
                                if  (!empty($lineSpec->getContext())){
                                    // stockage ligne courante et reset de l'object LineDocSpec
                                    $this->addTContextLine($lineSpec);
                                    $lineSpec = new LineDocSpec();
                                }
                                
                                $lineSpec->setContext($globalContext)
                                         ->setDesc($this->parser->text($description))
                                         ->setDate(date)
                                         ->setNeed(need);
                            } 
                        }  

                    }   
                    $in_description = true;
                }
    
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