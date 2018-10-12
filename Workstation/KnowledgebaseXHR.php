<?php

namespace Webkul\UVDesk\SupportCenterBundle\Workstation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class KnowledgebaseXHR extends Controller
{
    public function listFoldersXHR(Request $request)
    {  
        $response = new Response();
        $folderCollection = $this->getDoctrine()->getRepository('UVDeskSupportCenterBundle:Solutions')->getAllSolutions($request->query, $this->container);

        $response->setContent(json_encode($folderCollection));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function updateFolderXHR(Request $request)
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
