<?php

namespace NotesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use NotesBundle\Entity\Note;

class DefaultController extends Controller
{

    public function indexAction()
		{
			$em = $this->getDoctrine()->getManager();
			$notes = $em->getRepository('NotesBundle:Note')->findAll();
			if (!$notes) {
			// throw $this->createNotFoundException('Uh-oh, no Notes found! create a new one perhaps?');
		}
		return $this->render('NotesBundle:Default:index.html.twig',array('notes' => $notes));
}

	public function createNoteAction()
	{
		/*
		$form = $this->createForm(NoteType::class, new Note());
		$form->handleRequest($request);
		if ($form->isValid()) {
			// Sauvegarder la note dans la DB (a faire plus tard)
			var_dump($form);
			return $this->redirect($this->generateUrl('/'));			//?
		}
		return $this->render('NoteBundle:Default:index.html.twig');
		
		// array('form' => $form->createView()));
		*/
		$note = new Note();
    	$note->setTitle('SecondNote');
    	$note->setContent('The Second in a long series');

		 $em = $this->getDoctrine()->getManager();
		 $em->persist($note);
		 $em->flush();
		
		 return new Response('New note created! : '.$Note->getTitle());
		
	}
}


//! ajouter exception si pas de note dans twig. 