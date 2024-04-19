<?php 
/**
 * Le rôle de cette classe est de recuperer les fichiers md contenu dans la racine du dossier des modules custom
 * 
 * De les parser et de les injecter dans à la demande  au frontal 
 * 
 */
abstract class DocSpecObject {

   const RESOURCE_TYPE_SPECS='specs.md';
   const RESOURCE_TYPE_DOCS='docs.md';

   /** regex patterns definition  */
   // Element begin with [context-  et finissant par  ]
   const PATTERN_CONTEXT = "/\[context-(.*?)\]/";
   // Element begin with [need-  et finissant par  ]
   const PATTERN_NEED = "\[need-(.*?)\]";

   const PATTERN_CONTEXT_NEED = "/\[context-(.*?)\]\[need-(.*?)\]/";

   public $nameModule;

    //@abstract 
    // qui nous permet de renseigner les valeurs en tant que dev de la classe au moment de l'implementation
    abstract protected function setArrayContext(); 

    public function __construct(){
        $this->nameModule = ucfirst( basename(dirname(dirname((new ReflectionClass($this))->getFileName()))) );
    }
    /**
     * Magic method call from child
     */
    public function __toString()
    { 
        // Obtient le dossier parent du dossier contenant le fichier de la classe appelante
        
        return ucfirst( basename(dirname(dirname((new ReflectionClass($this))->getFileName()))) );
    }  

    /**
     * Récuperation en fonction du context courant  pour le fichier de ressource traité
     */
    public function loadResourcesFile($typeMarkdowFile, $currentContext){
        $TContextLine = [];
        $TTempContext = [];    
       // Récuperation du chemin de la classe enfant (héritage)
        $urlToFile = dirname(dirname((new ReflectionClass($this))->getFileName())) . '/'.$typeMarkdowFile;

        if (file_exists($urlToFile)) {

            dol_syslog('Found resources ' . $typeMarkdowFile . ' for <strong>'. $this->nameModule . '</strong> <br>',LOG_NOTICE);
            echo 'Found resources ' . $typeMarkdowFile . ' for <strong>'. $this->nameModule . '</strong> <br>';
            $file_handle = fopen( $urlToFile, 'r') or die("Unable to open file!");;
            
            function get_all_lines($file_handle) { 
                while (!feof($file_handle)) {
                    yield fgets($file_handle);
                }
            }
            $count = 0;
            foreach (get_all_lines($file_handle) as $line) {
                $count += 1;
               // echo $count.". ".$line  .'<br>';
                /** @todo parsing de la ligne pour récupération des clés [context] et [need] */
                
                if (!empty($line)){
                    if (preg_match($this::PATTERN_CONTEXT, $line, $matches)) {

                      // echo '<pre>' .  var_export($matches,true) . '</pre>';
                      // echo  $matches[1];

                        if ($currentContext == $matches[1]){
                            // empty temp array contact
                             $TTempContext = [];  
                             /** @todo remplir tmpcontext avec [context][date][exigangeNum] 
                              *  
                             */
                             if (preg_match_all($this::PATTERN_CONTEXT_NEED, $line, $matches)) { 
                                // echo '<pre>match_all -- ' .  var_export($matches,true) . '</pre>';
                              
                                // recupération de la description sans les tags context et need
                                $mDesc =  preg_split($this::PATTERN_CONTEXT_NEED, $line) ; 
                               // var_dump($mDesc);
                                $desc = array_key_exists(1, $mDesc) ? str_replace( "\\" , " ", $mDesc[1] )   :  "" ;
                                echo $desc.'<br>';
                                // echo '<pre>split -- ' .  var_export(preg_split($this::PATTERN_CONTEXT_NEED, $line),true) . '</pre>';
                             }else{
                                // pas d'entete , on stock la ligne complète
                                $desc = $line;
                             }

                        }else{
                          // pas le context courant ou description            

                        }
                        //$this->fetchLine($line);
                    }else{
                        // nous sommes dans le cas d'un multi lignes
                        // nous stockons temporairement ces lignes 
                    }
                }
                
            }
            fclose($file_handle);
        }else{
            dol_syslog('no resources ' . $typeMarkdowFile . ' for <strong>'. $this->nameModule . '</strong> <br>', LOG_NOTICE);
           //echo 'no resources ' . $typeMarkdowFile . ' for <strong>'. $this->nameModule . '</strong> <br>';
        }

        
    }


}