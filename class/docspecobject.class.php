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

    private function addLinesSpec($file_handle, $globalContext){

        $lineSpec = new LineDocSpec();
        $currentContext = "";

        foreach ($this->get_all_lines($file_handle) as $TextLine) {
            /** @todo parsing de la ligne pour récupération des clés [context] et [need] */
            if (!empty($TextLine)){
                // context déclaré dans la ligne
                preg_match($this::PATTERN_CONTEXT, $TextLine, $matches);
                 
                 /** @todo  voir pôur l'affichage de tous les context du module dans la page admin. on doit shinter ce test  ( || administration )   */ 

                 // est ce que j'ai une [context-xxx] pour cette ligne ? 
                if (key_exists(1, $matches)){
                  
                  $currentContext = $matches[1];      
                  // est ce que le context de ligne est egal au context global
                    if ($globalContext == $currentContext ){ 
                        $res = preg_match_all($this::PATTERN_LINE_DESCRIPTOR, $TextLine);
                        if ( $res) { 
                            if  (!empty($lineSpec->getContext())){
                                // stockage ligne courante et reset de l'object LineDocSpec
                                $this->addTContextLine($lineSpec);
                                $lineSpec = new LineDocSpec();
                            }
                            // recupération de la description sans les tags context et need
                            $mDesc =  preg_split($this::PATTERN_LINE_DESCRIPTOR, $TextLine) ; 
                            $desc = array_key_exists(1, $mDesc) ? str_replace( "\\" , " ", $mDesc[1] )   :  "" ;
                            /** @todo insertion bonnes valeurs */    
                            $lineSpec->setContext($globalContext)
                                     ->setDesc($this->parser->text($desc))
                                     ->setDate('2024-03-18')
                                     ->setNeed('14');
                        } 
                    }  
                }elseif  ( $globalContext == $currentContext ){
                    $lineSpec->setSubLine($this->parser->text($TextLine));    
                }     
            }
        }
        // last
        return $lineSpec;
    }


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