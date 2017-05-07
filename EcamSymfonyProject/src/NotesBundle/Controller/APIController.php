<?php

namespace NotesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\DBAL\DriverManager;


use NotesBundle\Entity\Note;
use NotesBundle\Entity\Category;

class APIController extends Controller{

// CORS management
	public function respondToOptionsAction($response){
		$response->headers->set('Content-Type', 'application/text');
		$response->headers->set('Access-Control-Allow-Origin', '*');
		$response->headers->set("Access-Control-Allow-Methods", "GET, PUT, POST, DELETE, OPTIONS");
	return $response;
	}	

// function returns all the notes 
	public function getNotesAction(){
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        	$response = new Response();
        	return $this->respondToOptionsAction($response);
        }
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery('SELECT n, c FROM NotesBundle\Entity\Note n JOIN n.category c');
		$notes = $query->getArrayResult();
	$response = new JsonResponse($notes);
	return $this->respondToOptionsAction($response);
	}

// function returns all the categories
	public function getCategoriesAction(){
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        	$response = new Response();
        	return $this->respondToOptionsAction($response);
        }
		$repo = $this->getDoctrine()->getRepository('NotesBundle:Category');
        $categories = $repo->createQueryBuilder('q')
           ->getQuery()
           ->getArrayResult();
    $response = new JsonResponse($categories);
	return $this->respondToOptionsAction($response);
	}

// function creates note, then redirects to list of notes
	public function createNoteAction(Request $request){
	   	if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	   	    $response = new Response();
        	return $this->respondToOptionsAction($response);
	   	}
		$info = $request->getContent();
		$data = json_decode($info,true);
		$note = new Note;
		$note->setTitle($data['title']);
		$note->setContent($data['content']);
		$note->setDate(new \DateTime($data['date']));
		$repo = $this->getDoctrine()->getRepository('NotesBundle:Category');
		$categoryLabel = $data['category'];
		$category = $repo->findOneBylabel($categoryLabel);
		$note->setCategory($category);

		$note = $this->generateXml($note);
			if ($note[0] == "xmlError") {
				$response = new JsonResponse($note[1],500);
				return $this->respondToOptionsAction($response);
			}
			try {
				$em = $this->getDoctrine()->getManager();
				$em->persist($note[0]);
				$em->flush();
			} catch (\Doctrine\DBAL\DBALException $e){
				$response = new JsonResponse('DB error! there is already a note with this name.',500);
				return $this->respondToOptionsAction($response);
			}
	return $this->getNotesAction();
	}

// function creates a category, then redirects to the list of categories
	public function createCategoryAction(Request $request){ 
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
			$response = new Response();
        	return $this->respondToOptionsAction($response);
		}
		$info = $request->getContent();
		$data = json_decode($info,true);
		$category = new Category;
		$category->setLabel($data['label']);
		$em = $this->getDoctrine()->getManager();
        try {
        	$em->persist($category);
        	$em->flush(); 
        } catch(\Doctrine\DBAL\DBALException $e) {
        	$response = new Response('Error, this category already exists!',500);
        	return $this->respondToOptionsAction($response);	
        }  		
	return $this->getCategoriesAction();		
	}

// function deletes note, redirects to list of notes
	public function deleteNoteAction($noteId){  
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        	$response = new Response();
        	return $this->respondToOptionsAction($response);
        }
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('NotesBundle:Note');
        $note = $repo->find($noteId);
        $em->remove($note);
        $em->flush();
    return $this->getNotesAction();
	}

// function deletes category, redirects to list of categories
	public function deleteCategoryAction($categoryId){
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        	$response = new Response();
        	return $this->respondToOptionsAction($response);
        }
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('NotesBundle:Category');
        $category = $repo->find($categoryId);
        try {
        	$em->remove($category);
        	$em->flush(); 
        } catch(\Doctrine\DBAL\DBALException $e) {
        	$response = new Response('Error, you can\'t delete a category if a note is atached to it!',500);
        	return $this->respondToOptionsAction($response);	
        }  
    return $this->getCategoriesAction();

	}	

// function gets the content of note being saved, sets content of note, then persists to DB.
	public function editNoteAction(Request $request){
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        	$response = new Response();
        	return $this->respondToOptionsAction($response);
        }

		$info = $request->getContent();
	   	$data = json_decode($info,true);
	   	$repo = $this->getDoctrine()->getRepository('NotesBundle:Note');
	   	$noteId = $data['id'];
		$note = $repo->findOneByid($noteId);

	   	$note->setTitle($data['title']);
	   	$note->setContent($data['content']);
		$note->setDate(new \DateTime($data['date']['date']));
		$repo = $this->getDoctrine()->getRepository('NotesBundle:Category');
		$categoryLabel = $data['category'];
		$category = $repo->findOneBylabel($categoryLabel);
		$note->setCategory($category);

		$note = $this->generateXml($note);
		
		if ($note[0] == "xmlError") {
			$response = new JsonResponse($note[1],500);
			return $this->respondToOptionsAction($response);
		}
		try {
			$em = $this->getDoctrine()->getManager();
			$em->persist($note[0]);
			$em->flush();
		} catch (\Doctrine\DBAL\DBALException $e){
			$response = new JsonResponse('Sorry, another note already has that name!',500);
			return $this->respondToOptionsAction($response);
		}		
		
	$response = new JsonResponse('It worked! Note edited.',200);
	return $this->respondToOptionsAction($response);			
	}

// function gets the content of the category being saved, sets contents of category, then persists to DB.
	public function editCategoryAction(Request $request){
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {return $this->respondToOptionsAction();}
		$info = $request->getContent();
	   	$data = json_decode($info,true);
	   	$repo = $this->getDoctrine()->getRepository('NotesBundle:Category');
	   	$categoryId = $data['id'];
		$category = $repo->findOneByid($categoryId);
		$category->setLabel($data['label']);
	   	$em = $this->getDoctrine()->getManager();
	   	$em->persist($category);
	   	$em->flush();
	$response = new JsonResponse('It worked! Category edited.',200);
	return $this->respondToOptionsAction($response);
	}

    /*		function adds <note><content> and </notes></content> to note content. Then loads it as XML and validates it.	*/ 
	public function generateXml($note) {
		$dom  = new \DOMDocument;
		$xml  = "<note><content>";
		$xml .= $note->getContent();
		$xml .= "</content></note>";
		try{
			$dom->loadXML($xml);
		}
		catch (\Exception $e){
			return (["xmlError","XML structure not valid"]);
		}
		try {
			$dom->schemaValidate('note.xsd');
		}
		catch (\Exception $e){
			return (["xmlError","XML schema not valid"]);
		}
		$note->setContent($xml);
		return ([$note]);
	}
        
}
