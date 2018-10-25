<?php

namespace Webkul\UVDesk\SupportCenterBundle\Workstation;

use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\SupportCenterBundle\Entity\Solutions;

class Folder extends Controller
{  
    public function listFolders(Request $request)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

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
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $folder = new Solutions();
        $errors = [];

        if ($request->getMethod() == "POST") {
            $entityManager = $this->getDoctrine()->getManager();
            $solutionImage = $request->files->get('solutionImage');
            
            if ($imageFile = $request->files->get('solutionImage')) {
                if (!preg_match('#^(image/)(?!(tif)|(svg) )#', $imageFile->getMimeType()) && !preg_match('#^(image/)(?!(tif)|(svg))#', $imageFile->getClientMimeType())) {
                   
                    $message = 'Warning! Provide valid image file. (Recommened: PNG, JPG or GIF Format).';
                    $this->addFlash('warning', $message);
                    
                    return $this->render('@UVDeskSupportCenter/Staff/Folders/createFolder.html.twig', [
                        'folder' => $folder
                    ]);
                }
            }
              
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
            $entityManager->persist($folder);
            $entityManager->flush();
            $message = 'Success! Folder has been added successfully.';

            $this->addFlash('success', $message);

            return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_folders_collection'));
        }

        return $this->render('@UVDeskSupportCenter/Staff/Folders/createFolder.html.twig', [
            'folder' => $folder,
            'errors' => json_encode($errors)
        ]);
    }

    public function updateFolder($folderId)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $knowledgebaseFolder = $entityManager->getRepository('UVDeskSupportCenterBundle:Solutions')->findSolutionById(['id' => $folderId]);

        if (empty($knowledgebaseFolder)) {
            $this->noResultFound();
        }

        if ($request->getMethod() == "POST") {
            $formData = $request->request->all();
            $solutionImage = $request->files->get('solutionImage');
          
            if ($imageFile = $request->files->get('solutionImage')) {
                if (!preg_match('#^(image/)(?!(tif)|(svg) )#', $imageFile->getMimeType()) && !preg_match('#^(image/)(?!(tif)|(svg))#', $imageFile->getClientMimeType())) {
                    $message = 'Warning! Provide valid image file. (Recommened: PNG, JPG or GIF Format).';
                    $this->addFlash('warning', $message);
                    
                    return $this->render('@UVDeskSupportCenter/Staff/Folders/updateFolder.html.twig', [
                        'folder' => $folder
                    ]);
                }
            }
            $formData = $request->request->all();
            if (isset($solutionImage)) {
                $knowledgebaseFolder->setSolutionImage($this->get('uvdesk.service')->getFileUploadManager()->upload($solutionImage));
            }

            $knowledgebaseFolder
                ->setName($formData['name'])
                ->setDescription($formData['description'])
                ->setvisibility($formData['visibility'])
                ->setDateUpdated( new \DateTime())
                ->setSortOrder(1);

            $entityManager->persist($knowledgebaseFolder);
            $entityManager->flush();
            
            $this->addFlash('success', 'Folder updated successfully.');
            return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_folders_collection'));
        }

        return $this->render('@UVDeskSupportCenter/Staff/Folders/updateFolder.html.twig', [
            'folder' => $knowledgebaseFolder,
            'errors' => json_encode(!empty($errors) ? $errors : [])
        ]);
    }
}
