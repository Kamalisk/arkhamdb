<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Faction;
use AppBundle\Form\FactionType;

/**
 * Faction controller.
 *
 */
class FactionController extends Controller
{

    /**
     * Lists all Faction entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Faction')->findAll();

        return $this->render('AppBundle:Faction:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Faction entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Faction();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_faction_show', array('id' => $entity->getId())));
        }

        return $this->render('AppBundle:Faction:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Faction entity.
     *
     * @param Faction $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Faction $entity)
    {
        $form = $this->createForm(new FactionType(), $entity, array(
            'action' => $this->generateUrl('admin_faction_create'),
            'method' => 'POST',
        ));

        return $form;
    }

    /**
     * Displays a form to create a new Faction entity.
     *
     */
    public function newAction()
    {
        $entity = new Faction();
        $form   = $this->createCreateForm($entity);

        return $this->render('AppBundle:Faction:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Faction entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Faction')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Faction entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Faction:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Faction entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Faction')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Faction entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Faction:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Faction entity.
    *
    * @param Faction $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Faction $entity)
    {
        $form = $this->createForm(new FactionType(), $entity, array(
            'action' => $this->generateUrl('admin_faction_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Faction entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Faction')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Faction entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('admin_faction_edit', array('id' => $id)));
        }

        return $this->render('AppBundle:Faction:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Faction entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Faction')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Faction entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_faction'));
    }

    /**
     * Creates a form to delete a Faction entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_faction_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
