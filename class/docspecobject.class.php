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
	 *
	 */
   public function getContextLines(){
	   return $this->TContextLines;
	}
    /**
     * @param LineDocSpec $line
     */
   public function addTContextLine($line){
	   if ($line->getContext() != null) $this->TContextLines[] = $line;
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

	/**
	 * @param $typeMarkdowFile
	 * @return string
	 */
    private function getPathToFile($typeMarkdowFile){
		global $dolibarr_main_document_root_alt;
        return $dolibarr_main_document_root_alt .'/'. $this->nameModule . '/'.$typeMarkdowFile;
    }

    /**
     * travail depuis un fichier pour stockage dans l'objet
     */
    private function addLinesSpec($file_handle, $globalContext){

        $lineSpec = new LineDocSpec();
        $currentContext = "";
        $description_buffer = "";
        $in_description = false;
		// ligne à ligne
        foreach ($this->get_all_lines($file_handle) as $TextLine) {

            if (!empty($TextLine)){
				// suis je sur une ligne de tags déclaratif ?
                if (preg_match(self::GLOBAL_REGEX, $TextLine, $matches)) {

                    if ($in_description) $in_description = false;
					$contextsLine = [];
					$contextsLine = $this->extractLabelsContexts($matches[0], $contextsLine);

					//si je suis sur la declaration des tags de context de la ligne courante (multicontext possible) et que ce context est utilisé dans le fichier de specs
                    if ( !empty($contextsLine) && in_array($globalContext, $contextsLine )) {
						//echo var_export($contexts, true);
						preg_match(self::NEED_REGEX, $matches[0], $needMatch);
						$need = $needMatch[1];

						preg_match(self::DATE_REGEX, $matches[0], $dateMatch);
						$date = $dateMatch[1];


						// Le context de lineDesc a t'il etait déclaré ?
						// Alors Stockage ligne courante et reset de l'object LineDocSpec
						if  (!empty($lineSpec->getContext())){
							$this->addTContextLine($lineSpec);
							$lineSpec = new LineDocSpec();
						}
						// remplissage nouvelle entête
						$lineSpec->setContext($globalContext)->setDate($date)->setNeed($need);

						// flag passage sur ligne de description (idéaliement c'est une fonction de linespec
						$in_description = true;

					}else{ // la ligne de tagq n'est pas dans le context on réinitialise l'object
						if  (!empty($lineSpec->getContext())){
							$this->addTContextLine($lineSpec);
							$lineSpec = new LineDocSpec();
						}
						//je dois clôture l'obect etle stoquer'
						// je gère la description ici
						//$in_description = true;
					}

				// je ne suis pas sur une ligne de tags déclaratif et que la description est dans le context
                }elseif ($lineSpec->getContext() == $globalContext){

					$lineSpec->setSubLine($this->parser->text($TextLine));
				}

            }
        }
        // last insertion
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

	/**
	 * @param $matches
	 * @param $contextMatches
	 * @param array $contexts
	 * @return array
	 */
	public function extractLabelsContexts($matches,  array $contexts): array
	{
		preg_match_all(self::CONTEXT_REGEX, $matches, $contextMatches);

		foreach ($contextMatches[1] as $context) {
			$contexts[] = $context;
		}
		return $contexts;
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

    public function getSubLineElement($index) {
        return  $this->subLine[$index];
    }
    public function getContext(){
        return $this->context;
    }
    public function getNeed(){
        return $this->need;
    }
    public function getDate(){
        return $this->date;
    }
    public function getDesc(){
        return $this->desc;
    }



}
