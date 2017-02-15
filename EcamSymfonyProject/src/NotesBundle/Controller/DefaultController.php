<?php

namespace NotesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use NotesBundle\Entity\Note;
use NotesBundle\Entity\Category;
// use NotesBundle\Form\NoteType;

class DefaultController extends Controller{

	public function getNotesAction(){
		$em = $this->getDoctrine()->getManager();
		$notes = $em->getRepository('NotesBundle:Note')->findAll();
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

	public function createNoteAction(Request $request){

		$note = new Note();
		return $this->editNoteAction($note,$request);
	}

	public function createCategoryAction(Request $request){

		$category = new Category();
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
        }
		$em = $this->getDoctrine()->getEntityManager();
        $em->remove($category);
        $em->flush();
        
        // return new Response('Note deleted!');
        $this->addFlash('notice', 'Category has been deleted!');
        return $this->redirectToRoute('listCategories');
        
	}	

	public function editNoteAction(Note $note, Request $request){

		$categories = $this->getCategoriesAction();
		$form = $this->createFormBuilder($note)
			->add('title', TextType::class, array('label' => 'Note Title'))
			->add('content', TextareaType::class, array('label' => 'Note Content'))
			->add('date',DateType::class,array('label'=>'Date:','widget'=>'choice'))
			->getForm();

			if ($note->getId() != 0){
				$form->add('save', SubmitType::class, array('label' => 'Edit your note','attr' => array('class'=>'btn btn-primary')));	
			}
			else {
				$form->add('save', SubmitType::class, array('label' => 'Add note to collection','attr' => array('class'=>'btn btn-primary')));	
			}

			foreach ($categories as $category){
				$test[$category->getlabel()] = $category;
			}
		
			$form->add('category', ChoiceType::class, array(
				'label' => 'Note Category',
				'choices' => $test)
			);
			

		$form->handleRequest($request);
		$note = $form->getData();
		
		if ($form->isValid()) {
			$em = $this->getDoctrine()->getManager();
			$em->persist($note);
			$em->flush();

			if ($note->getId() != 0){
				$this->addFlash('success', 'Note updated!');}
			else { 
				$this->addFlash('success', 'New note created!');}
			
			return $this->redirectToRoute('notes_homepage');
		}

		return $this->render('NotesBundle:Default:noteForm.html.twig', array('form' => $form->createView(),'note'=>$note));

	}

	public function editCategoryAction(Category $category, Request $request){

		$form = $this->createFormBuilder($category)
			->add('label', TextType::class, array('label' => 'Category Label'))
			->getForm();

			if ($category->getId() != 0){
				$form->add('save', SubmitType::class, array('label' => 'Edit this category','attr' => array('class'=>'btn btn-primary')));	
			}
			else {
				$form->add('save', SubmitType::class, array('label' => 'Add new category','attr' => array('class'=>'btn btn-primary')));	
			}

		$form->handleRequest($request);
		$category = $form->getData();
		
		if ($form->isValid()) {
			$em = $this->getDoctrine()->getManager();
			$em->persist($category);
			$em->flush();

			if ($category->getId() != 0){
				$this->addFlash('success', 'Category updated!');}
			else { 
				$this->addFlash('success', 'New category created!');}
			
			return $this->redirectToRoute('listCategories');
		}

		return $this->render('NotesBundle:Default:categoryForm.html.twig', array('form' => $form->createView(),'category'=>$category));

	}
        
}





















/*
		$form = $this->createForm(NoteType::class, new Note());
		$form->handleRequest($request);

		if ($form->isValid()) {
			// Sauvegarder la note dans la DB (a faire plus tard)
			var_dump($form);
			return $this->redirect($this->generateUrl('/'));			//?
		}
		return $this->render('NotesBundle:Default:noteCreation.html.twig',array('form' => $form->createView()));
		
		// array('form' => $form->createView()));
		// $note = new Note();
    	// $note->setTitle('FourthNote');
    	// $note->setContent('The Fourth in a long series');

		 // $em = $this->getDoctrine()->getManager();
		 // $em->persist($note);
		 // $em->flush();
		
		 // return new Response('New note created! : '.$Note->getTitle());*/

//! ajouter exception si pas de note dans twig. 