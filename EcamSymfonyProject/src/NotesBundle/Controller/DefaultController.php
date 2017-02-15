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
// use NotesBundle\Form\NoteType;

class DefaultController extends Controller{

    public function indexAction(){
			$em = $this->getDoctrine()->getManager();
			$notes = $em->getRepository('NotesBundle:Note')->findAll();
			if (!$notes) {
				//throw $this->createNotFoundException('Uh-oh, no Notes found! create a new one perhaps?');
				$this->addFlash('notice', 'Oops! there are no notes yet! create one perhaps?');
		}
		return $this->render('NotesBundle:Default:index.html.twig',array('notes' => $notes));
	}

	public function createNoteAction(Request $request){

		$note = new Note();
		return $this->editNoteAction($note,$request);
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

	public function editNoteAction(Note $note, Request $request){

		$form = $this->createFormBuilder($note)
			->add('title', TextType::class, array('label' => 'Note Title'))
			->add('content', TextareaType::class, array('label' => 'Note Content'))
			->add('category', ChoiceType::class, array(
				'label' => 'Note Category',
				'choices' => array('Category1'=>'Category1',
								   'Category2'=>'Category2',
								   'Category3'=>'Category3'))
			)
			->add('date',DateType::class,array('label'=>'Date:','widget'=>'choice'))
			->getForm();

			if ($note->getId() != 0){
				$form->add('save', SubmitType::class, array('label' => 'Edit your note','attr' => array('class'=>'btn btn-primary')));	
			}
			else {
				$form->add('save', SubmitType::class, array('label' => 'Add note to collection','attr' => array('class'=>'btn btn-primary')));	
			}

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