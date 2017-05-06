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
	// function retreaves all notes
	public function getNotesAction(){
		$em = $this->getDoctrine()->getManager();
		$notes = $em->getRepository('NotesBundle:Note')->findAll();
		$searchNotes = array();
		$searchTerm = Null;
		/*	if the user has made a search, save the search term for future use.	*/
		if (isset($_POST['search'])) {
			$searchTerm = $_POST['search'];	
		}	
		/*  for each note: get array of tags present in the note, remove the <xml><content>... 
		 	for each tag in array, compare to see if it corresponds to the search, 
		 	add note to new array of notes containing matches.	*/
		foreach($notes as $note) {
			$dom = new \DOMDocument();
			$content = $note->getContent();
			$dom->loadXML($content);
			$tagColl = $dom->getElementsByTagName("tag");
			$note = $this->parseXml($note);

			foreach ($tagColl as $tag) {
				$compare = $tag->nodeValue;
				if ($compare == $searchTerm){
					$searchNotes[] = $note;
				}
			}
		}
		/*	check if the user has made a search for tags.		
			if no search, return array with all notes.	*/	
		if ($searchTerm != ""){
			return $searchNotes;
		}
		return $notes;
	}
	/* 		function returns array with all categories from DB. 	*/
	public function getCategoriesAction(){
		$em = $this->getDoctrine()->getManager();
		$categories = $em->getRepository('NotesBundle:Category')->findAll();
		return $categories;	
	}
	/* 		function renders twig for list of notes. Calls getNotes function, adds warning if no results.	*/
    public function indexAction(){
		$notes = $this->getNotesAction();
		if (!$notes) {
			$this->addFlash('notice', 'There are no notes yet, create one perhaps?');
		}
		return $this->render('NotesBundle:Default:index.html.twig',array('notes' => $notes));
	}
	/*		function renders twig for list of categories. Calls getCategories function, adds warning if no results.		*/
    public function listCategoriesAction(){
			$categories = $this->getCategoriesAction();
			if (!$categories) {
				$this->addFlash('notice', 'Oops! there are no categories yet! create one perhaps?');
		}
		return $this->render('NotesBundle:Default:listCategories.html.twig',array('categories' => $categories));
	}
	/* 		function creates new Note based on Entity. Adds "isnew" property to note, will be used to determine what text to put in the
			buttons etc to show user. Functino then passes not to editNote function to add data to the note before persist to DB. 	*/
	public function createNoteAction(Request $request) {
		$note = new Note();
		$note->isNew = true;
		return $this->editNoteAction($note,$request);
	}
	/*		function creates new Category based on Entity. Adds "isnew" property to category, will be used to determine what text to put in the
			buttons and texts to show user. function then passes category to EditCategor function to add data to the category before persist to DB 	*/
	public function createCategoryAction(Request $request) {
		$category = new Category();
		$category->isNew=true;
		return $this->editCategoryAction($category,$request);
	}
	/* 		function deletes note. Adds flash message for user on success, redirects to list of notes	*/
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
	/*		function detes Category. Catches error if user deletes a category with notes that belong to it. We cannot 'orphan' the notes
			by deleting a category they belong to. Adds message for user in case of success.	*/
	public function deleteCategoryAction(Category $category){
        if (!$category) {
            throw $this->createNotFoundException('Error! no Category found for id '.$id);
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
	/*		function edits Note. Starts by taking current note, sends it to newOrUpdateMessage function to know what to add in the buttons, whether it
			be "create note" or "edit Note"	*/
	public function editNoteAction(Note $note, Request $request){
		$note->type = "note";
		$categories = $this->getCategoriesAction();
		$messageArray=$this->newOrUpdateMessage($note);
		$note = $this->parseXml($note);
		/*		check if there are categories, if not ask user to create one first (or else choice list is empty)	*/
		if (empty($categories)){
			$this->addFlash('error', 'wait! you need to create a category first.');
			return $this->redirectToRoute('notes_homepage');
		}
		/*		function gets each category to be added to the select list of note form.	*/
		foreach ($categories as $category){
			$choiceArray[$category->getlabel()] = $category;
		}
		/*		Build the form with the appropriate text to show user	*/
		$form = $this->createFormBuilder($note)
			->add('title', TextType::class, array('label' => 'Note Title'))
			->add('content', TextareaType::class, array('label' => 'Note Content'))
			->add('date',DateType::class,array('label'=>'Date:','widget'=>'choice'))
			->add('save', SubmitType::class, array('label' => $messageArray["saveButton"],'attr' => array('class'=>'btn btn-primary')))
			->add('category', ChoiceType::class, array('label' => 'Note Category','choices' => $choiceArray))
			->getForm();			
		$form->handleRequest($request);
		$note = $form->getData();
		/*		note is sent to generateXml function. This function will format the content of the note as xml, can return in case of error. Note is then persisted to DB. Errors are caught and sent to the user via Flashbag.	*/
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
		/*		if success, add message to user and redirect to list of notes.	*/
		$this->addFlash('success', $messageArray["flashMessage"]);			
		return $this->redirectToRoute('notes_homepage');
		}
		return $this->render('NotesBundle:Default:noteForm.html.twig', array('form' => $form->createView(),'note'=>$note));
	}
	/*		function very similar to previous editNote Function, but for Categories.		*/
	public function editCategoryAction(Category $category, Request $request){
		$category->type="category";
		$messageArray=$this->newOrUpdateMessage($category);
		/*		build form		*/
		$form = $this->createFormBuilder($category)
			->add('label', TextType::class, array('label' => 'Category Label'))
			->add('save', SubmitType::class, array('label' => $messageArray["saveButton"],'attr' => array('class'=>'btn btn-primary')))
			->getForm();
		$form->handleRequest($request);
		$category = $form->getData();
		/*		check if the form is valid.	Catch SQL errors, add message for user if success or error.		*/
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
	/*		function returns array with what to display in form button. ex: "create" or "Edit" Note. Also returns flash 		message		*/
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
	/*		function removes the <content><note> and </note></content> tags from note content 		*/
	public function parseXml($note){
		// 	we don't parse a note if it is new, it has to be created
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
    /*		function adds <note><content> and </notes></content> to note content. Then loads it as XML and validates it.	*/ 
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
