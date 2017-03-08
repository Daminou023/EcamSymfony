<?php

namespace NotesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use NotesBundle\Entity\Note;
use NotesBundle\Entity\Category;
// use NotesBundle\Form\NoteType;

class APIController extends Controller{

	public function getNotesAction(){
        $repo = $this->getDoctrine()->getRepository('NotesBundle:Note');
        $notes = $repo->createQueryBuilder('q')
           ->getQuery()
           ->getArrayResult();
        return new JsonResponse($notes);
	}

	public function getCategoriesAction(){
		$repo = $this->getDoctrine()->getRepository('NotesBundle:Category');
        $categories = $repo->createQueryBuilder('q')
           ->getQuery()
           ->getArrayResult();
        return new JsonResponse($categories);
	}

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

	public function deleteNoteAction($noteId){        
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('NotesBundle:Note');
        $note = $repo->find($noteId);
        $em->remove($note);
        $em->flush();   
        return $this->redirect($this->generateUrl('API_Notes'));
	}

	public function deleteCategoryAction($categoryId){
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('NotesBundle:Category');
        $category = $repo->find($categoryId);
        $em->remove($category);
        $em->flush();   
        return $this->redirect($this->generateUrl('API_Categories'));

	}	

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
