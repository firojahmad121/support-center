<?php

namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Webkul\UVDesk\SupportCenterBundle\Entity\Article;
use Webkul\UVDesk\SupportCenterBundle\Entity\ArticleCategory;
use Webkul\UVDesk\SupportCenterBundle\Entity\ArticleHistory;
use Webkul\UVDesk\SupportCenterBundle\Entity\SolutionCategory;
use Webkul\UVDesk\SupportCenterBundle\Form;

class ArticleController extends Controller
{
    public function articleListAction(Request $request)
    {
       
        $solutions = $this->getDoctrine()
                           ->getRepository('UVDeskSupportCenterBundle:Solutions')
                           ->getAllSolutions(null, $this->container, 'a.id, a.name');
        if($solutions){
            foreach($solutions as $key => $solution){
                $solutions[$key]['categories'] = $this->getDoctrine()
                                                    ->getRepository('UVDeskSupportCenterBundle:Solutions')
                                                    ->getCategoriesWithCountBySolution($solution['id']);
            }
        }
  

        return $this->render('@UVDeskSupportCenter/Front/articleList.html.twig', [
            'solutions' => $solutions
        ]);
    }

    public function articleListByCategoryAction(Request $request)
    {
      

        $category = $this->getDoctrine()
                            ->getRepository('UVDeskSupportCenterBundle:SolutionCategory')
                            ->findCategoryById(['id' => $request->attributes->get('category')]);

        if($category){
            return $this->render('UVDeskSupportCenterBundle:Default:articleListByCategory.html.twig', [
                'category' => $category,
                'articleCount' => $this->getDoctrine()
                                        ->getRepository('UVDeskSupportCenterBundle:SolutionCategory')
                                        ->getArticlesCountByCategory($request->attributes->get('category')),
                'categorySolutions' => $this->getDoctrine()
                                        ->getRepository('UVDeskSupportCenterBundle:SolutionCategory')
                                        ->getSolutionsByCategory($request->attributes->get('category')),
                'solutions' => $this->getDoctrine()
                                    ->getRepository('UVDeskSupportCenterBundle:Solutions')
                                    ->getAllSolutions(null, $this->container, 'a.id, a.name')
            ]);
        }else
            $this->noResultFound();
    }

    public function ArticleListBySolutionAction(Request $request)
    {
        

        $solution = $this->getDoctrine()
                            ->getRepository('UVDeskSupportCenterBundle:Solutions')
                            ->findSolutionById(['id' => $request->attributes->get('solution')]);

        if($solution){
            return $this->render('@UVDeskSupportCenter/Front/articleListBySolution.html.twig', [
                'solution' => $solution,
                'solutionArticleCount' => $this->getDoctrine()
                            ->getRepository('UVDeskSupportCenterBundle:Solutions')
                            ->getArticlesCountBySolution($request->attributes->get('solution')),
                'solutionCategoryCount' => $this->getDoctrine()
                            ->getRepository('UVDeskSupportCenterBundle:Solutions')
                            ->getCategoriesCountBySolution($request->attributes->get('solution')),
            ]);
        }else
            $this->noResultFound();
    }

    public function articleListXhrAction(Request $request)
    {
        $json = array();
       
        $repository = $this->getDoctrine()->getRepository('UVDeskSupportCenterBundle:Article');
        
        if($request->attributes->get('category'))
        $request->query->set('categoryId', $request->attributes->get('category'));
        
        if($request->attributes->get('solution'))
        $request->query->set('solutionId', $request->attributes->get('solution'));

        $json = $repository->getAllArticles($request->query, $this->container);
        
  
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function articleHistoryXhrAction(Request $request)
    {
        $json = array();
        $repository = $this->getDoctrine()->getRepository('UVDeskSupportCenterBundle:Article');

        $params = [
            'articleId' => $request->attributes->get('id')
        ];

        $json = $repository->getAllHistoryByArticle($params);

        if($json)
            foreach($json as $key => $js){
                $json[$key]['dateAdded'] = [
                    'format' => $this->container->get('user.service')->convertToTimezone($js['dateAdded']),
                    'timestamp' => $this->container->get('user.service')->convertToDatetimeTimezoneTimestamp($js['dateAdded']),
                ];
            }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function translatedArticleHistoryXhrAction(Request $request)
    {
        $json = array();
        $repository = $this->getDoctrine()->getRepository('UVDeskSupportCenterBundle:TranslatedArticle');

        $articleId = $request->attributes->get('id');      

        $json = $repository->getAllTranslatedHistoryByArticle($articleId);
        if($json) {
            foreach($json as $key => $js) {
                $json[$key]['dateAdded'] = [
                    'format' => $this->container->get('user.service')->convertToTimezone($js['dateAdded']),
                    'timestamp' => $this->container->get('user.service')->convertToDatetimeTimezoneTimestamp($js['dateAdded']),
                ];
            }
        }

        return new JsonResponse($json);
    }


    public function articleRelatedXhrAction(Request $request)
    {
        $json = array();
        $repository = $this->getDoctrine()->getRepository('UVDeskSupportCenterBundle:Article');

        $params = [
            'articleId' => $request->attributes->get('id')
        ];

        $json = $repository->getAllRelatedyByArticle($params);

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    protected function getArticle($filterArray = array())
    {
        if($filterArray)
            return $this->getDoctrine()
                        ->getRepository('UVDeskSupportCenterBundle:Article')
                        ->findOneBy($filterArray);
        return false;
    }

    public function articleAction(Request $request)
    {
        if($request->attributes->get('id')){
            $article = $this->getArticle(
                [
                    'id' => $request->attributes->get('id')
                ]
            );
            if(!$article)
                $this->noResultFound();
        } else
            $article = new Article;

        $articleCategory = $articleTags = [];
        if($article->getId()) {
             $articleCategory = $this->getDoctrine()
                             ->getRepository('UVDeskSupportCenterBundle:Article')
                             ->getCategoryByArticle($article->getId());
             $articleTags = $this->getDoctrine()
                             ->getRepository('UVDeskSupportCenterBundle:Article')
                             ->getTagsByArticle($article->getId());
        }

        $errors = [];
        $categories = $this->getDoctrine()
                            ->getRepository('UVDeskSupportCenterBundle:SolutionCategory')
                            ->getAllCategories(null, $this->container, 'a.id, a.name');

        if($request->attributes->get('id')) {
            $em = $this->getDoctrine()->getManager();
            $translatedArticle = $em->getRepository('UVDeskSupportCenterBundle:TranslatedArticle')->getTranslatedArticleByArticle($article);


            $article = [
                'article' => $article,
                'articleCategory' => $articleCategory,
                'articleTags' => $articleTags,
                'categories' => $categories,
                'errors' => json_encode($errors),
                'translatedArticles' => $translatedArticle
            ];
            return  $this->render('@UVDeskSupportCenter/Front/articleForm.html.twig', $article);
        }

        return $this->render('@UVDeskSupportCenter/Front/articleAddForm.html.twig', [
            'article' => $article,
        ]);
    }
    public function articleXhrAction(Request $request)
    {
    
        $json = array();
       
        if($request->getMethod() == "POST") {
            $em = $this->getDoctrine()->getManager();

            $data = $request->request->get("data");
            if(isset($data['actionType']))
                switch($data['actionType']){
                    case 'articleUpdate':
                    case 'articleSave':                        
                        if('articleSave' == $data['actionType']  && !empty($resources['articles']['showAlert']) ) {
                            $json['alertClass'] = 'danger';
                            return new JsonResponse($json);
                        }
                        if($data['ids'][0]){
                            $article = $this->getArticle(
                                [
                                    'id' => $data['ids'][0],
                                ]
                            );
                        }else{
                            $article = new Article;
                        }
                        $json['errors'] = [];
                        if($article){
                            if(strlen($data['name']) > 200) {
                                $json['errors']['name'] = $this->get('translator')->trans('Name length must not be greater than 200 !!');
                            }

                            
                            if(!$json['errors']) {
                                unset($json['errors']);
                                $article->setName($data['name']);
                                $article->setSlug($data['slug']);
                                $article->setMetaTitle($data['metaTitle']);
                                $article->setKeywords($data['keywords']);
                                $article->setMetaDescription($data['metaDescription']);
                              
                                $updateRevisionHistory = false;


                                if ($article->getContent() == null || trim($article->getContent()) != trim($data['content'])) {
                                  
                                    $updateRevisionHistory = true;
                                    $article->setContent($data['content']);
                                }
                                
                                $em->persist($article);
                                $em->flush();
                                foreach($this->get('support.service')->getLocales() as $localeCode => $locale ) {
                                    if( isset($data[$localeCode]['name']) ) {
                                        $transArticleData = $data[$localeCode];
                                        $translateArticleRepo = $em->getRepository('UVDeskSupportCenterBundle:TranslatedArticle');
                                        $transArticle = $translateArticleRepo->findOneBy(['article' => $article, 'locale' => $localeCode]);

                                        if($data[$localeCode]['name']) {
                                            if($transArticle) {
                                                if($transArticleData['content'] !== $transArticle->getContent() ) {
                                                    $this->updateTranslateContent($transArticle, $transArticleData['content']);
                                                }
                                                $transArticle->setDetails($transArticleData);

                                                $em->persist($transArticle);
                                                $em->flush();
                                            } else {
                                                $transArticle = new TranslatedArticle();
                                                $transArticle->setLocale($localeCode);
                                                $transArticle->setArticle($article);                                               
                                                $transArticle->setDetails($transArticleData);

                                                $em->persist($transArticle);
                                                $em->flush();
                                            }
                                        } else {
                                            if($transArticle) {
                                                $em->remove($transArticle);
                                                $em->flush();
                                            }
                                        }
                                    }
                                }

                                $json['alertClass'] = 'success';
                                $json['alertMessage'] = $this->get('translator')->trans('article.update.success');

                                if(!$data['ids'][0]){
                                    $json['redirect'] = $this->generateUrl('article_edit_action', array('id' => $article->getId()));
                                }

                            }else{
                                $json['alertClass'] = 'danger';
                                $json['alertMessage'] = $this->get('translator')->trans('Warning! Correct all field values first!');
                            }
                        }else{
                            $json['alertClass'] = 'danger';
                            $json['alertMessage'] = 'Warning ! This is not a valid request';
                        }
                        break;
                    case 'status':
                        $em->getRepository('UVDeskSupportCenterBundle:Article')->bulkArticleStatusUpdate($data['ids'], $data['targetId']);
                        $json['alertClass'] = 'success';
                        $json['alertMessage'] = 'Success ! Article updated successfully.';
                        break;
                    case 'contentUpdate':
                        $article = $this->getArticle(
                            [
                                'id' => $data['ids'][0],
                            ]
                        );

                        if($article) {
                            if(trim($article->getContent()) != trim($data['content']))
                                $this->updateContent($article, $data['content']);

                            $json['alertClass'] = 'success';
                            $json['alertMessage'] = 'Success ! Revision restored successfully.';
                        }else{
                            $json['alertClass'] = 'danger';
                            $json['alertMessage'] = 'Warning ! This is not a valid request' ;
                        }

                        break;
                    case 'translateContentUpdate':
                        $article = $this->getArticle(
                            [
                                'id' => $data['ids'][0],
                            ]
                        );

                        if($article && isset($data['locale'])) {
                            $translatedArticle = $em->getRepository('UVDeskSupportCenterBundle:TranslatedArticle')->findOneBy([
                                'article' => $article,
                                'locale' => $data['locale'],
                            ]);

                            if(trim($translatedArticle->getContent()) != trim($data['content']))
                                $this->updateTranslateContent($translatedArticle, $data['content']);

                            $json['alertClass'] = 'success';
                            $json['alertMessage'] = $this->translate('Success ! Revision restored successfully.');
                        } else {
                            $json['alertClass'] = 'danger';
                            $json['alertMessage'] = $this->translate('Warning ! This is not a valid request');
                        }

                        break;
                    case 'categoryUpdate':
                        if($data['action'] == 'remove'){
                            $this->getDoctrine()
                                ->getRepository('UVDeskSupportCenterBundle:Article')
                                ->removeCategoryByArticle($data['ids'][0], [$data['entityId']]);

                        }elseif($data['action'] == 'add'){
                           
                            $articleCategoryMapping = new ArticleCategory();
                            $articleCategoryMapping->setArticleId($data['ids'][0]);
                            $articleCategoryMapping->setCategoryId($data['entityId']);
                            $em->persist($articleCategoryMapping);
                            $em->flush();
                        }
                        $json['alertClass'] = 'success';
                        $json['alertMessage'] = 'Success ! Categories updated successfully.';
                        break;
                    case 'relatedUpdate':
                        if($data['action'] == 'remove'){
                            $this->getDoctrine()
                                ->getRepository('UVDeskSupportCenterBundle:Article')
                                ->removeRelatedByArticle($data['ids'][0], [$data['entityId']]);

                            $json['alertClass'] = 'success';
                            $json['alertMessage'] = 'Success ! Article Related removed successfully.';
                        }elseif($data['action'] == 'add'){
                            $company = $this->container->get('user.service')->getCurrentCompany();

                            $relatedArticles = $em->getRepository('UVDeskSupportCenterBundle:ArticleRelatedArticle')->findBy([
                                'articleId' => $data['ids'][0],
                                'companyId' => $company->getId(),
                                'relatedArticleId' => $data['entityId'],
                            ]);

                            if(count($relatedArticles)) {
                                $json['alertClass'] = 'success';
                                $json['alertMessage'] ='Success ! Article Related is already added.';
                            } elseif($data['ids'][0] == $data['entityId']) {
                                $json['alertClass'] = 'danger';
                                $json['alertMessage'] = 'Success ! Cannot add self as relative article.';
                            } else {
                                $articleRelatedMapping = new ArticleRelatedArticle();
                                $articleRelatedMapping->setArticleId($data['ids'][0]);
                                $articleRelatedMapping->setRelatedArticleId($data['entityId']);
                                $articleRelatedMapping->setCompanyId($company->getId());
                                $em->persist($articleRelatedMapping);
                                $em->flush();

                                $json['alertClass'] = 'success';
                                $json['alertMessage'] = 'Success ! Article Related updated successfully.';
                            }
                        }

                        break;
                    
                    case 'delete':
                        if($data['ids']){
                            foreach($data['ids'] as $id){
                                $article = $em->getRepository('UVDeskSupportCenterBundle:Article')->find($id);
                                if($article){
                                  
                                    $em->remove($article);
                                    $em->flush();
                                }
                            }
                            $this->removeArticle($article);
                        }
                        $json['alertClass'] = 'success';
                        $json['alertMessage'] = 'Success ! Articles removed successfully.';
                        break;
                    default:
                        $json['alertClass'] = 'danger';
                        $json['alertMessage'] = 'Warning ! This is not a valid request';
                }
        }elseif($request->getMethod() == "PATCH") {
            $em = $this->getDoctrine()->getManager();

            $data = json_decode($request->getContent(), true);
            
            if(isset($data['editType']))
                switch($data['editType']){
                    case 'status':
                        $em->getRepository('UVDeskSupportCenterBundle:Article')->bulkArticleStatusUpdate([$data['id']], $data['value']);
                        $json['alertClass'] = 'success';
                        $json['alertMessage'] = 'Success ! Article status updated successfully.';
                        break;
                    case "stared":
                        $article = $em->getRepository('WebkulSupportCenterBundle:Article')->findOneBy(['id' => $data['id']]);
                        if($article) {
                            $article->setStared( (isset($data['value']) && $data['value'] == 1) ? 1 : 0 );
                            $em->persist($article);
                            $em->flush();
                        }
                        $json['alertClass'] = 'success';
                        $json['alertMessage'] ='Success ! Article star updated successfully.';
                        break;
                    case "update":
                        $articleBase = $this->getDoctrine()
                            ->getRepository('UVDeskSupportCenterBundle:SolutionCategory')
                            ->find($data['id']);

                        if($articleBase){
                            if(isset($data['name']) && strlen($data['name']) > 200) {
                                $json['alertClass'] = 'danger';
                                $json['alertMessage'] = $this->get('translator')->trans('Name length must not be greater than 200 !!');
                            } else {
                                $articleBase->setName($this->get('support.service')->htmlfilter($data['name']));

                                if(trim($articleBase->getContent()) != trim($data['content']))
                                    $this->updateContent($request, $articleBase, $data['content']);

                                $json['alertClass'] = 'success';
                                $json['alertMessage'] = $this->get('translator')->trans('article.update.success');
                            }
                        }
                    
                        case 'status':
                        $em->getRepository('WebkulSupportCenterBundle:Article')->bulkArticleStatusUpdate([$data['id']], $data['value']);
                        $json['alertClass'] = 'success';
                        $json['alertMessage'] = $this->translate('Success ! Article status updated successfully.');
                        break;
                    
                    default:
                        $json['alertClass'] = 'danger';
                        $json['alertMessage'] = $this->translate('Warning ! This is not a valid request');
                }
        }
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function updateContent($articleBase, $content, $updateArticle = true)
    {
        $em = $this->getDoctrine()->getManager();
        //entry for Article History
        $articleHistory = new ArticleHistory;
        // $articleHistory->setCompanyId($this->getCompany()->getId());
        $articleHistory->setUserId($this->getUser()->getId());
        $articleHistory->setArticleId($articleBase->getId());
        $articleHistory->setContent($articleBase->getContent());

        if ($updateArticle) {
            // $articleBase->setContent($this->get('support.service')->htmlfilter($content));
            $articleBase->setContent($content);
            $em->persist($articleBase);
        }

        $em->persist($articleHistory);
        $em->flush();
    }

    private function updateTranslateContent($transArticleBase, $content)
    {
        $em = $this->getDoctrine()->getManager();
        //entry for translate Article History
        $transArticleHistory = new TranslatedArticleHistory;
        $transArticleHistory->setCompanyId($this->getCompany()->getId());
        $transArticleHistory->setUserId($this->getUser()->getId());
        $transArticleHistory->setTranslatedArticleId($transArticleBase->getId());
        $transArticleHistory->setContent($transArticleBase->getContent());

        $transArticleBase->setContent($this->get('support.service')->htmlfilter($content));
        $em->persist($transArticleHistory);
        $em->persist($transArticleBase);
        $em->flush();
    }

    private function removeArticle($article)
    {
        $this->getDoctrine()
            ->getRepository('UVDeskSupportCenterBundle:Article')
            ->removeEntryByArticle($article->getId());
    }

    public function articleRelatedXhr2Action(Request $request)
    {
        $json = array();       
        if($request->getMethod() == "GET") {

        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
