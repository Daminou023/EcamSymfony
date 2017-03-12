<?php

namespace NotesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use NotesBundle\Entity\Note;
use NotesBundle\Entity\Category;

class DefaultController extends Controller{

	public function getNotesAction(){
		$em = $this->getDoctrine()->getManager();
		$notes = $em->getRepository('NotesBundle:Note')->findAll();
		$searchNotes = array();
		$searchTerm = Null;

		if (isset($_POST['search'])) {
			$searchTerm = $_POST['search'];	
		}	
				
		foreach($notes as $note) {
			$dom = new \DOMDocument();
			$content = $note->getContent();
			$dom->loadXML($content);
			$tag = $dom->getElementsByTagName("tag");
			$note = $this->parseXml($note);
			foreach ($tag as $tag) {
				$compare = $tag->nodeValue;
				if ($compare == $searchTerm){
					$searchNotes[] = $note;
				}
			}
		}

		if ($searchTerm != ""){
			return $searchNotes;
		}
		return $notes;
	}


	public function getCategoriesAction(){
		$em = $this->getDoctrine()->getManager();
		$categories = $em->getRepository('NotesBundle:Category')->findAll();
		return $categories;	
	}

    public function indexAction(){
		$notes = $this->getNotesAction();
		if (!$notes) {
			$this->addFlash('notice', 'Oops! there are no notes yet! create one perhaps?');
		}
		return $this->render('NotesBundle:Default:index.html.twig',array('notes' => $notes));
	}

    public function listCategoriesAction(){
			$categories = $this->getCategoriesAction();
			if (!$categories) {
				$this->addFlash('notice', 'Oops! there are no categories yet! create one perhaps?');
		}
		return $this->render('NotesBundle:Default:listCategories.html.twig',array('categories' => $categories));
	}

	public function createNoteAction(Request $request) {
		$note = new Note();
		$note->isNew=true;
		return $this->editNoteAction($note,$request);
	}

	public function createCategoryAction(Request $request) {
		$category = new Category();
		$category->isNew=true;
		return $this->editCategoryAction($category,$request);
	}

	public function deleteNoteAction(Note $note){

        if (!$note) {
            throw $this->createNotFoundException('Error! no note found for id '.$id);
        }
		$em = $this->getDoctrine()->getEntityManager();
        $em->remove($note);
        $em->flush();
        
        // return new Response('Note deleted!');
        $this->addFlash('notice', 'Note has been deleted!');
        return $this->redirectToRoute('notes_homepage');
        
	}

	public function deleteCategoryAction(Category $category){

        if (!$category) {
            throw $this->createNotFoundException('Error! no note found for id '.$id);
        } try 
        {
			$em = $this->getDoctrine()->getEntityManager();
	        $em->remove($category);
	        $em->flush();
	    } catch(\Doctrine\DBAL\DBALException $e){
			$this->addFlash('error', 'Can\'t delete category as notes belond to it, delete notes first!');
			return $this->redirectToRoute('listCategories');
		}
        
        $this->addFlash('notice', 'Category has been deleted!');
        return $this->redirectToRoute('listCategories');
	}	

	public function editNoteAction(Note $note, Request $request){

		$note->type="note";
		$categories = $this->getCategoriesAction();
		$messageArray=$this->newOrUpdateMessage($note);
		$note = $this->parseXml($note);

		foreach ($categories as $category){
			$choiceArray[$category->getlabel()] = $category;
		}

		$form = $this->createFormBuilder($note)
			->add('title', TextType::class, array('label' => 'Note Title'))
			->add('content', TextareaType::class, array('label' => 'Note Content'))
			->add('date',DateType::class,array('label'=>'Date:','widget'=>'choice'))
			->add('save', SubmitType::class, array('label' => $messageArray["saveButton"],'attr' => array('class'=>'btn btn-primary')))
			->add('category', ChoiceType::class, array('label' => 'Note Category','choices' => $choiceArray))
			->getForm();
			
		$form->handleRequest($request);
		$note = $form->getData();
		
		if ($form->isValid()) {
			$note = $this->generateXml($note);
			if ($note == "xmlError") {
				return $this->redirectToRoute('notes_homepage');
			}
			try {
				$em = $this->getDoctrine()->getManager();
				$em->persist($note);
				$em->flush();
			} catch (\Doctrine\DBAL\DBALException $e){
				$this->addFlash('error', 'Two notes can\'t have the same title!');
				return $this->render('NotesBundle:Default:noteForm.html.twig', array('form' => $form->createView(),'note'=>$note));
			}

		$this->addFlash('success', $messageArray["flashMessage"]);			
		return $this->redirectToRoute('notes_homepage');
		}
		return $this->render('NotesBundle:Default:noteForm.html.twig', array('form' => $form->createView(),'note'=>$note));
	}


	public function editCategoryAction(Category $category, Request $request){
		
		$category->type="category";
		$messageArray=$this->newOrUpdateMessage($category);
		
		$form = $this->createFormBuilder($category)
			->add('label', TextType::class, array('label' => 'Category Label'))
			->add('save', SubmitType::class, array('label' => $messageArray["saveButton"],'attr' => array('class'=>'btn btn-primary')))
			->getForm();

		$form->handleRequest($request);
		$category = $form->getData();
		
		if ($form->isValid()) {
			try{
				$em = $this->getDoctrine()->getManager();
				$em->persist($category);
				$em->flush();
			} catch (\Doctrine\DBAL\DBALException $e){
				$this->addFlash('error', 'Category already exists!');
				return $this->render('NotesBundle:Default:categoryForm.html.twig', array('form' => $form->createView(),'category'=>$category));
			}			
			$this->addFlash('success', $messageArray["flashMessage"]);		
			return $this->redirectToRoute('listCategories');
		}

		return $this->render('NotesBundle:Default:categoryForm.html.twig', array('form' => $form->createView(),'category'=>$category));
	}

	public function newOrUpdateMessage($element){
		if ($element->getId()!=0){
			$textArray = [
				"saveButton"=>"Edit this $element->type",
				"flashMessage" => "$element->type updated!"];
		}
		else {
			$textArray = [
				"saveButton"=>"Save this $element->type",
				"flashMessage" => "New $element->type created!"];
		}
		return $textArray;
	}

	public function parseXml($note){

		if ($note->getId()==0) {
			return $note;
		}	

		$content = $note->getContent();
		$content = substr($content,15);
		$length = strlen($content);
		$content = substr($content,0,$length-17);
		$note->setContent($content);
		return $note;

	}
     
	public function generateXml($note){
		$dom  = new \DOMDocument;
		$xml  = "<note><content>";
		$xml .= $note->getContent();
		$xml .= "</content></note>";
		try{
			$dom->loadXML($xml);
		}
		catch (\Exception $e){
			$this->addFlash('error', 'XML structure not valid!');
			return ("xmlError");
		}
		try {
			$dom->schemaValidate('note.xsd');
		}
		catch (\Exception $e){
			$this->addFlash('error', 'XML not valid!');
			return ("xmlError");
		}
		$note->setContent($xml);
		return $note;
	}

}
