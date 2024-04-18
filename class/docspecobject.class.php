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
     * 
     */
    public function loadResources($typeMarkdowFile){

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
                echo $count.". ".$line  .'<br>';
                /** @todo parsing de la ligne pour récupération des clés [context] et [need] */
                $patternContext = "/\[context-(.*?)\]/";
                $patternNeed = "\[need-(.*?)\]";
                if (!empty($line)){
                    if (preg_match($patternContext, $line, $matches)) {
                       echo '<pre>' .  var_export($matches,true) . '</pre>';
                        echo  $matches[1];
                        //$this->fetchLine($line);
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