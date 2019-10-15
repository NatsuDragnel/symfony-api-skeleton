<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\ParameterBag;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Util\StringUtil;
use Symfony\Contracts\Translation\TranslatorInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;

/**
 * Repository
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 */
class Repository
{
	/**
	 * @var ParameterBag
	 */
	private $query;
	
	/**
	 * @var EntityManager
	 */
	private $entityManager;
	
	/**
	 * @var RegistryInterface
	 */
	private $doctrine;
	
	/**
	 * @var TranslatorInterface
	 */
	private $translator;
	
	/**
	 * @var SerializerInterface
	 */
	private $serializer;
	
	/**
	 * @var string
	 */
	private $entityName;
	
	/**
	 * @var string
	 */
	private $tableName;
	
	/**
	 * @var array
	 */
	private $filters;
	
	/**
	 * @var array
	 */
	private $filtersMEMBEROF;
	
	/**
	 * @var array
	 */
	private $excludes;
	
	/**
	 * @var array
	 */
	private $blameables;
	
	/**
	 * @var integer
	 */
	private $defaultLimit;
	
	/**
	 * @var integer
	 */
	private $defaultPage;
	
	/**
	 * Default serialize group
	 * @var string
	 */
	private $defaultGroup;
	
	
	public function __construct(
	    RegistryInterface $doctrine, 
	    TranslatorInterface $translator,
	    SerializerInterface $serializer,
	    $defaultLimit, 
	    $defaultPage,
	    $defaultGroup,
	    $excludes, 
	    $blameables
	){
	    $this->doctrine = $doctrine;
	    $this->translator = $translator;
	    $this->serializer = $serializer;
	    $this->defaultLimit = $defaultLimit;
	    $this->defaultPage = $defaultPage;
	    $this->defaultGroup = $defaultGroup;
	    $this->excludes = $excludes;
	    $this->blameables = $blameables;
	}
	
	/**
	 * Filter a resource
	 * 
	 * @param ParameterBag $query
	 * @param string $entityName
	 * @param string $connection
	 * @return array
	 */
	public function filter(ParameterBag $query, $entityName, $connection = 'default')
	{
		$this->query = $query;
		$this->entityName = $entityName;
		$this->entityManager = $this->doctrine->getManager($connection);
		$this->tableName = $this->entityManager->getClassMetadata($this->entityName)->getTableName();
		$this->filters = $this->entityManager->getClassMetadata($this->entityName)->getFieldNames();
		
		// Construct DQL
		$response = self::constructCriteria();
		
		if (count($response['errors']) > 0) {
		    return $response['errors'];
		}
		
		// Paginate resources
		return self::paginate($response['criteria'], $response['params']);
	}
	
	/**
	 * Construct criteria
	 * 
	 * @return array
	 */
	public function constructCriteria() {
		$criteria = '';
		$params = [];
		$errors = [];
		$filters = $this->sanitizeQueryParams();
		$count = 0;
		
		if (count($filters) > 0) {
		    foreach ($filters as $key => $value) {
		        $response = self::getPartOfCriteria($key, $value, $criteria, $params);
		        
		        if (isset($response['status']) && $response['status'] === false) {
		            $errors[] = $response['message'];
		        }else {
		            $criteria = $response['criteria'];
		            $params = $response['params'];
		            
		            if ($count < count($filters) - 1) {
		                $criteria .= ' and ';
		            }
		            
		            $count ++;
		        }
		    }
		}
		
		return ['criteria' => $criteria, 'params' => $params, 'errors' => $errors];
	}
	
	
	/**
	 *  Paginate results if necessary
	 * 
	 * @param array $criteria
	 * @param array $params
	 * @return number[]|mixed[]|\Doctrine\DBAL\Driver\Statement[]|array[]|NULL[]
	 */
	public function paginate(string $criteria, array $params = []) {
	    $dql = self::dqlFilter($criteria);
	    
	    $resources = $this->entityManager
                	      ->createQuery($dql)
                	      ->setParameters($params)
                	      ->getResult();
	   
        if (count($resources) == 1) {
            return $this->applySerializerContext($resources[0], $this->query->get('options'), $this->query->get('fields'));
        }
        
		// Limit Results
		if (! $this->query->get('limit')) {
			$limit = $this->defaultLimit;
		}else{
			if ($this->query->get('limit') <= count($resources)) {
				$limit = $this->query->get('limit');
			}else{
				$limit = count($resources);
			}
		}

		// Total pages
		$totalPages = round(count($resources) / $limit);
		
		// Current page
		$currentPage = $this->query->get('page');
		$currentPage = $currentPage && ($currentPage > 0 || $currentPage < $totalPages ) ? $currentPage : $this->defaultPage;
		$currentPage = $currentPage > $totalPages ? $totalPages : $currentPage;
		$currentPage = $currentPage == 0 ? 1 : $currentPage;
		
		// Offset
		$offset = ($currentPage - 1) * $limit;
		
		// Paginate
		$resources = $this->entityManager
					      ->createQuery($dql)
						  ->setFirstResult($offset)
						  ->setMaxResults($limit)
						  ->setParameters($params)
						  ->getResult();
       
		return [
		    'items' => $this->applySerializerContext($resources, $this->query->get('options'), $this->query->get('fields')), 
			'currentPage' => $currentPage, 
			'lastPage' => $totalPages,
			'limit'	=> $limit
		];
	}
	
	
	/**
	 * Prepare DQL for Filtering
	 *
	 * @param string $criteria
	 * @return string
	 */
	public function dqlFilter($criteria) {
	    $fields = 'object';
	    if ($this->query->get('fields')) {
	        $array = explode(',', $this->query->get('fields'));
	        foreach ($array as $key => $item) {
	            $array[$key] = 'object.'.$item;
	        }
	        
	        $fields = implode(',', $array);
	    }
	    
	    $dql = 'SELECT '.$fields.' FROM '.$this->entityName. ' object ';
	    $dql = ! $criteria ? $dql : $dql.'WHERE '.$criteria;
	    
	    // Order by
	    if ($dql && $this->query->get('orderBy')){
	        $array = explode(':', $this->query->get('orderBy'));
	        $array[0] = StringUtil::transformToCamelCase($array[0]);
	        
	        $dql .= ' ORDER BY object.'.$array[0].' '.$array[1];
	    }
	    
	    return $dql;
	}
	
	
	/**
	 * Remove excludes query params
	 * 
	 * @return array
	 */
	public function sanitizeQueryParams() {
	    $queryParamsKeys = array_diff(array_keys($this->query->all()), $this->excludes);
	    $queryParams = [];
	    
	    foreach ($queryParamsKeys as $key) {
	        if ($this->query->get($key) !== null) {
	            $queryParams[$key] = $this->query->get($key);
	        }
	    }
	    
	    return $queryParams;
	}
	
	/**
	 * Get supported operators
	 * @return array
	 */
	public static function supportedOperators() {
	    return ['^', '$', '@', '!', ':', '<', '<=', '>', '>='];
	}
	
	/**
	 * Test if field is valid
	 * 
	 * @param string $field
	 * @return boolean
	 */
	public function isValidField(string $field) {
	    return true === in_array($field, $this->filters) ||
	           true === in_array($field, $this->excludes) ||
	           true === in_array($field, $this->blameables);
	}
	
	
	/**
	 * Get part of criteria
	 *
	 * @param  string   $expression
	 * @param  string   $criteria
	 * @param  array    $params
	 * @return array
	 */
	public function getPartOfCriteria(string $key, string $expression, string $criteria, array $params) {
	    $key = StringUtil::transformToCamelCase($key);
	    $currentOperator = null;
	    $value = null;
	    $operators = self::supportedOperators();
	    
	    if (false === $this->isValidField($key)) {
	        return [
	            'status' => false,
	            'message' => $this->translator->trans('message.400.unknow_field', ['%field%' => $key], 'error')
	        ];
	    }
	    
	    foreach ($operators as $operator) {
	        $value = str_replace($operator, '', $expression);
	        if ($expression != $value) {
	            $currentOperator = $operator;
	            break;
	        }
	    }
	    
	    switch ($currentOperator) {
	        case "^": // Begin by
	            $olds = preg_grep("#:".$key."#", array_keys($params));
	            $keyToReplace = $key.'_'.count($olds);
	            $criteria .= "object.".$key." LIKE :".$keyToReplace;
	            $params[':'.$keyToReplace] = "'" .$value. "%'";
	            break;
	        case "@": // Contains
	            $criteria .= "object.".$key." LIKE '%" .$value. "%'";
	            break;
	        case "!@": // Not Contains
	            $criteria .= " NOT object.".$key." LIKE '%" .$value. "%'";
	            break;
	        case "$": // End by
	            $criteria .= "object.".$key." LIKE '%" .$value. "'";
	            break;
	        case ":": // IN
	            $array = explode(";", $value);
	            foreach ($array as $index => $item){
	                $array[$index] = "'".$item."'";
	            }
	            
	            $criteria .= 'object.'.$key.' IN ('.implode(",", $array).')';
	            break;
	        case "!": // NOT
	            $olds = preg_grep("#:".$key."#", array_keys($params));
	            $keyToReplace = $key.'_'.count($olds);
	            $criteria .= "object.".$key." != :".$keyToReplace;
	            $params[':'.$keyToReplace] = $value;
	            break;
	        case "<": // Lower than
	            $olds = preg_grep("#:".$key."#", array_keys($params));
	            $keyToReplace = $key.'_'.count($olds);
	            $criteria .= "object.".$key." < :".$keyToReplace;
	            $params[':'.$keyToReplace] = $value;
	            break;
	        case "<=": // Lower than
	            $olds = preg_grep("#:".$key."#", array_keys($params));
	            $key = $key.'_'.count($olds);
	            $criteria .= "object.".$key." <= :".$key;
	            $params[':'.$keyToReplace] = $value;
	            break;
	        case ">": // Greater than
	            $olds = preg_grep("#:".$key."#", array_keys($params));
	            $keyToReplace = $key.'_'.count($olds);
	            $criteria .= "object.".$key." > :".$keyToReplace;
	            $params[':'.$keyToReplace] = $value;
	            break;
	        case ">=": // Greater than
	            $olds = preg_grep("#:".$key."#", array_keys($params));
	            $keyToReplace = $key.'_'.count($olds);
	            $criteria .= "object.".$key." >= :".$keyToReplace;
	            $params[':'.$keyToReplace] = $value;
	            break;
	        case "": // Equal
	            if ($value === "NULL") {
	                $criteria .= "object.".$key." IS NULL";
	            }else {
	                $olds = preg_grep("#:".$key."#", array_keys($params));
	                $keyToReplace = $key.'_'.count($olds);
	                $criteria .= "object.".$key." = :".$keyToReplace;
	                $params[':'.$keyToReplace] = $value;
	            }
	            break;
	        default:
	            return ['status' => false, 'message' => 'unable to evaluate expression '.$expression];
	    }
	    
	    return ['criteria' => $criteria, 'params' => $params];
	}
	
	/**
	 * Apply serializer context group
	 * 
	 * @param mixed $items
	 * @return mixed
	 */
	public function applySerializerContext($items, string $group = null, string $fields = null) {
	    if (! empty($items) && $fields === null) {
	        $group = $group ?? $this->defaultGroup;
	        $items = $this->serializer->serialize($items, 'json', SerializationContext::create()->setGroups(array('Default', $group)));
	        $items = json_decode($items, true);
	    }
	    
	    return $items;
	}
}