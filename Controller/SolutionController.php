<?php

namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\SupportCenterBundle\Controller\BaseController;

use Webkul\UVDesk\SupportCenterBundle\Entity\Solutions;
use Symfony\Component\Form\FormError;
use Webkul\UVDesk\SupportCenterBundle\Form\Solution;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
// class SolutionController extends BaseController
class SolutionController extends Controller
{   
	/**
	 * View Solution Lists
	 */
    public function SolutionListAction(Request $request)
    {	
        $solutionCount = $this->getDoctrine()
                        ->getRepository('UVDeskSupportCenterBundle:Solutions')
                        ->getTotalSolutionCount();
        $categoryCount = $this->getDoctrine()
                        ->getRepository('UVDeskSupportCenterBundle:SolutionCategory')
                        ->getTotalCategoryCount();
        $articleCount = $this->getDoctrine()
                        ->getRepository('UVDeskSupportCenterBundle:Article')
                        ->getTotalArticleCount();
       
        $solutions = [
                'solutionCount' => $solutionCount,
                'categoryCount' => $categoryCount,
                'articleCount' => $articleCount,
        ];
        return $this->render('@UVDeskSupportCenter/Front/solutionList.html.twig',$solutions);
    }

    public function SolutionListXhrAction(Request $request)
    {  
        $json = array();
        $repository = $this->getDoctrine()->getRepository('UVDeskSupportCenterBundle:Solutions');
        $json =  $repository->getAllSolutions($request->query, $this->container);

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function SolutionAction(Request $request)
    {
        if($request->attributes->get('id')){
            $folder = $this->getDoctrine()->getRepository('UVDeskSupportCenterBundle:Solutions')->findSolutionById([
                                    'id' => $request->attributes->get('id'),
                            ]);
            if(!$folder)
                $this->noResultFound();
        } else
            $folder = new Solutions;

        $errors = [];
        if($request->getMethod() == "POST") {
            $solutionImage = $request->files->get('solutionImage');

            $form = $this->createForm(Solution::class, $folder);
           
            $form->handleRequest($request);
          
            if($imageFile = $request->files->get('solutionImage')) {
                if(!preg_match('#^(image/)(?!(tif)|(svg) )#', $imageFile->getMimeType()) && !preg_match('#^(image/)(?!(tif)|(svg))#', $imageFile->getClientMimeType()) ) {
                    $form->get('solutionImage')->addError(new FormError($this->translate('Provide valid image file. (Recommened: PNG, JPG or GIF Format)')));
                }
            }
            $form->submit(true);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $em = $this->getDoctrine()->getManager();
                $data = $request->request->all();
                $folder->setName($data['name']);
                $folder->setDescription($data['description']);
                $folder->setvisibility($data['visibility']);
                if(isset($solutionImage)){
                    $fileName  = $this->container->get('fileupload.service')->upload($solutionImage);
                    $folder->setSolutionImage($fileName);
                } 
                $folder->setDateAdded( new \DateTime());
                $folder->setDateUpdated( new \DateTime());
                $folder->setSortOrder(1);
                $em->persist($folder);
                $em->flush();
                $message = 'Success! Folder has been added successfully.';

                $this->addFlash('success', $message);

                return $this->redirect($this->generateUrl('webkul_support_center_solution'));
            } else {
                
                $errors = $this->getFormErrors($form);
            }
        }

        return $this->render('@UVDeskSupportCenter/Front/solutionForm.html.twig', [
                'folder' => $folder,
                'errors' => json_encode($errors)
            ]);
    }

    public function SolutionXhrAction(Request $request)
    {
       
        $json = array();
        if($request->getMethod() == "PATCH") { //UPDATE STATUS
            $em = $this->getDoctrine()->getManager();
            $content = json_decode($request->getContent(), true);
            $solutionId = $content['id'];
            $solution = $em->getRepository('UVDeskSupportCenterBundle:Solutions')->find($solutionId);
            if($solution) {
                switch($content['editType']){
                    case 'status':
                        $solution->setVisibility($content['value']);
                        $em->persist($solution);
                        $em->flush();
                        
                        $json['alertClass'] = 'success';
                        $json['alertMessage'] = 'Success ! Folder status updated successfully.';
                        break;
                    default:
                        break;
                }
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = $this->translate('Error ! Folder is not exist.');
            }
        } elseif($request->getMethod() == "PUT") {
            $em = $this->getDoctrine()->getManager();
            $content = json_decode($request->getContent(), true);
            $solutionId = $content['id'];
            $solution = $em->getRepository('UVDeskSupportCenterBundle:Solutions')->find($solutionId);
            if($solution) {
                    $form = $this->createFormBuilder($solution, [ 
                            'data_class' => 'Webkul\UVDesk\SupportCenterBundle\Entity\Solutions',                            
                        ])
                        ->add('name', TextType::class)
                        ->add('description', TextareaType::class)
                        ->getForm();
                
                       
                $form->submit(true);
                $form->handleRequest($request);
                if ($form->isSubmitted()) {
                    $solution->setName($content['name']);
                    $solution->setDescription($content['description']);
                    $em->persist($solution);
                    $em->flush();
                    
                    $json['alertClass'] = 'success';
                    $json['alertMessage'] ='Success ! Folder updated successfully.';
                } else {
                    $json['alertClass'] = 'danger';
                    $json['errors'] = json_encode($form->getErrors());
                }
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = 'Error ! Folder does not exist.';
            }
        } else if($request->getMethod() == "DELETE") {
            $solutionId = $request->attributes->get('id');

            $em = $this->getDoctrine()->getManager();
            $solutionBase = $em->getRepository('UVDeskSupportCenterBundle:Solutions')->find($solutionId);

            if($solutionBase){
                $em->getRepository('UVDeskSupportCenterBundle:Solutions')->removeEntryBySolution($solutionId);

                $em->remove($solutionBase);
                $em->flush();

                $json['alertClass'] = 'success';
                $json['alertMessage'] = $this->get('translator')->trans('solution.deleteFolder.success');
            }else{

                $json['alertClass'] = 'error';
                $json['alertMessage'] = $this->translate("Warning ! Folder doesn't exists!");
            }
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
