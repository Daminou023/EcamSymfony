<?php

namespace NotesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use NotesBundle\Entity\Note;
use NotesBundle\Entity\Category;

class APIController extends Controller{

// function returns all the notes 
	public function getNotesAction(){
        $repo = $this->getDoctrine()->getRepository('NotesBundle:Note');
        $notes = $repo->createQueryBuilder('q')
           ->getQuery()
           ->getArrayResult();
        return new JsonResponse($notes);
	}
// function returns all the categories
	public function getCategoriesAction(){
		$repo = $this->getDoctrine()->getRepository('NotesBundle:Category');
        $categories = $repo->createQueryBuilder('q')
           ->getQuery()
           ->getArrayResult();
        return new JsonResponse($categories);
	}
// function creates note, then redirects to list of notes
	public function createNoteAction(Request $request){
	   $info = $request->getContent();
	   $data = json_decode($info,true);
	   $note = new Note;
	   $note->setTitle($data['title']);
	   $note->setContent($data['content']);
	   $note->setDate(new \DateTime($data['date']['date']));
	   $repo = $this->getDoctrine()->getRepository('NotesBundle:Category');
	   $categoryLabel = $data['category'];
	   $category = $repo->findOneBylabel($categoryLabel);
	   $note->setCategory($category);
	   $em = $this->getDoctrine()->getManager();
	   $em->persist($note);
	   $em->flush();
	   return $this->redirect($this->generateUrl('API_Notes'));
	}
// function creates a category, then redirects to the list of categories
	public function createCategoryAction(Request $request){ 
	   $info = $request->getContent();
	   $data = json_decode($info,true);
	   $category = new Category;
	   $category->setLabel($data['label']);
	   $em = $this->getDoctrine()->getManager();
	   $em->persist($category);
	   $em->flush();
	   return $this->redirect($this->generateUrl('API_Categories'));
	}
// function deletes note, redirects to list of notes
	public function deleteNoteAction($noteId){  
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('NotesBundle:Note');
        $note = $repo->find($noteId);
        $em->remove($note);
        $em->flush();   
        return $this->redirect($this->generateUrl('API_Notes'));
	}
// function deletes category, redirects to list of categories
	public function deleteCategoryAction($categoryId){
        
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') 
			{
				$response = new Response();
				$response->headers->set('Content-Type', 'application/text');
				$response->headers->set('Access-Control-Allow-Origin', '*');
				$response->headers->set("Access-Control-Allow-Methods", "GET, PUT, POST, DELETE, OPTIONS");
				return $response;
			}

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('NotesBundle:Category');
        $category = $repo->find($categoryId);

        try {
        	$em->remove($category);
        	$em->flush(); 
        } catch(\Doctrine\DBAL\DBALException $e) {
        	return new Response('Well this is one hell of a pickle!',500);
        }  
        return $this->redirect($this->generateUrl('API_Categories'));

	}	
// function gets the content of note being saved, sets content of note, then persists to DB.
	public function editNoteAction(Request $request){
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
		$em = $this->getDoctrine()->getManager();
		$em->persist($note);
		$em->flush();
		return $this->redirect($this->generateUrl('API_Notes'));
	}

// function gets the content of the category being saved, sets contents of category, then persists to DB.
	public function editCategoryAction(Request $request){
		$info = $request->getContent();
	   	$data = json_decode($info,true);
	   	$repo = $this->getDoctrine()->getRepository('NotesBundle:Category');

	   	$categoryId = $data['id'];
		$category = $repo->findOneByid($categoryId);

		$category->setLabel($data['label']);
	   	$em = $this->getDoctrine()->getManager();
	   	$em->persist($category);
	   	$em->flush();
	   	return $this->redirect($this->generateUrl('API_Categories'));
	}
        
}
