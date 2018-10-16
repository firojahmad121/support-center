<?php

namespace Webkul\UVDesk\SupportCenterBundle\Workstation;

use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Webkul\UVDesk\SupportCenterBundle\Entity\Solutions;
use Symfony\Component\Form\FormError;
use Webkul\UVDesk\SupportCenterBundle\Form\Solution as SolutionForm;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class Folder extends Controller
{   
    public function listFolders(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $totalKnowledgebaseFolders = $entityManager->getRepository('UVDeskSupportCenterBundle:Solutions')->getTotalSolutionCount();
        $totalKnowledgebaseCategories = $entityManager->getRepository('UVDeskSupportCenterBundle:SolutionCategory')->getTotalCategoryCount();
        $totalKnowledgebaseArticles = $entityManager->getRepository('UVDeskSupportCenterBundle:Article')->getTotalArticleCount();

        return $this->render('@UVDeskSupportCenter/Staff/Folders/listFolders.html.twig', [
            'articleCount' => $totalKnowledgebaseArticles,
            'categoryCount' => $totalKnowledgebaseCategories,
            'solutionCount' => $totalKnowledgebaseFolders,
        ]);
    }

    public function createFolder(Request $request)
    {
        $folder = new Solutions();
        $errors = [];

        if ($request->getMethod() == "POST") {
            $solutionImage = $request->files->get('solutionImage');

            $form = $this->createForm(SolutionForm::class, $folder);
           
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
                    $fileName  = $this->container->get('uvdesk.service')->getFileUploadManager()->upload($solutionImage);
                    $folder->setSolutionImage($fileName);
                } 
                $folder->setDateAdded( new \DateTime());
                $folder->setDateUpdated( new \DateTime());
                $folder->setSortOrder(1);
                $em->persist($folder);
                $em->flush();
                $message = 'Success! Folder has been added successfully.';

                $this->addFlash('success', $message);

                return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_folders_collection'));
            } else {
                
                $errors = $this->getFormErrors($form);
            }
        }

        return $this->render('@UVDeskSupportCenter/Staff/Folders/createFolder.html.twig', [
                'folder' => $folder,
                'errors' => json_encode($errors)
            ]);
    }

    public function updateFolder($folderId)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $knowledgebaseFolder = $entityManager->getRepository('UVDeskSupportCenterBundle:Solutions')->findSolutionById(['id' => $folderId]);

        if (empty($knowledgebaseFolder)) {
            $this->noResultFound();
        }

        if ($request->getMethod() == "POST") {
            $formData = $request->request->all();
            $solutionImage = $request->files->get('solutionImage');
            $form = $this->createForm(SolutionForm::class, $knowledgebaseFolder);
           
            $form->handleRequest($request);
          
            if ($imageFile = $request->files->get('solutionImage')) {
                if(!preg_match('#^(image/)(?!(tif)|(svg) )#', $imageFile->getMimeType()) && !preg_match('#^(image/)(?!(tif)|(svg))#', $imageFile->getClientMimeType()) ) {
                    $form->get('solutionImage')->addError(new FormError($this->translate('Provide valid image file. (Recommened: PNG, JPG or GIF Format)')));
                }
            }

            $form->submit(true);
            
            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $request->request->all();
                if (isset($solutionImage)) {
                    $knowledgebaseFolder->setSolutionImage($this->get('uvdesk.service')->getFileUploadManager()->upload($solutionImage));
                }

                $knowledgebaseFolder
                    ->setName($formData['name'])
                    ->setDescription($formData['description'])
                    ->setvisibility($formData['visibility'])
                    ->setDateAdded( new \DateTime())
                    ->setDateUpdated( new \DateTime())
                    ->setSortOrder(1);

                $entityManager->persist($knowledgebaseFolder);
                $entityManager->flush();
                
                $this->addFlash('success', 'Folder updated successfully.');
                return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_folders_collection'));
            } else {
                $errors = $this->getFormErrors($form);
            }
        }

        return $this->render('@UVDeskSupportCenter/Staff/Folders/updateFolder.html.twig', [
            'folder' => $knowledgebaseFolder,
            'errors' => json_encode(!empty($errors) ? $errors : [])
        ]);
    }
}
