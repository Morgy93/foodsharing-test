<?php

namespace Foodsharing\RestApi;

use Foodsharing\Lib\Session;
use Foodsharing\Modules\Blog\BlogGateway;
use Foodsharing\Permissions\BlogPermissions;
use Foodsharing\Utility\Sanitizer;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class BlogpostController extends AbstractFOSRestController
{
    public function __construct(
        private readonly BlogGateway $blogGateway,
        private readonly BlogPermissions $blogPermissions,
        private readonly Session $session,
        private readonly Sanitizer $sanitizer,
    ) {
    }

    /**
     * Returns a specific blog post.
     *
     * @OA\Parameter(name="blogPostId", in="path", @OA\Schema(type="integer"), description="which post to return")
     * @OA\Response(response="200", description="Success.", @Model(type=BlogPost::class))
     * @OA\Response(response="404", description="Blog post not found.")
     * @OA\Tag(name="blog")
     * @Rest\Get("blog/{blogPostId}", requirements={"blogPostId" = "\d+"})
     */
    public function getBlogpostAction(int $blogPostId): Response
    {
        $blogPost = $this->blogGateway->getPost($blogPostId);

        if (is_null($blogPost)) {
            throw new NotFoundHttpException('Blog post not found');
        }

        $blogPost->content = $this->sanitizer->purifyHtml($blogPost->content);

        return $this->handleView($this->view($blogPost, 200));
    }

    /**
     * Publishes (isPublished=1) or depublishes (isPublished=0) a blogpost.
     *
     * @OA\Parameter(name="blogId", in="path", @OA\Schema(type="integer"), description="which post to (de)publish")
     * @OA\Response(response="200", description="Success.")
     * @OA\Response(response="401", description="Not logged in.")
     * @OA\Response(response="403", description="Insufficient permissions to manage this blogpost.")
     * @OA\Response(response="404", description="Blogpost not found.")
     * @OA\Tag(name="blog")
     * @Rest\Patch("blog/{blogId}", requirements={"blogId" = "\d+"})
     * @Rest\RequestParam(name="isPublished", requirements="(0|1)")
     */
    public function setBlogpostPublishedAction(int $blogId, ParamFetcher $paramFetcher): Response
    {
        $sessionId = $this->session->id();
        if (!$sessionId) {
            throw new UnauthorizedHttpException('', 'Not logged in.');
        }

        $author = $this->blogGateway->getAuthorOfPost($blogId);
        if ($author === false) {
            throw new NotFoundHttpException('Blogpost not found.');
        }
        if (!$this->blogPermissions->mayPublish($blogId)) {
            throw new AccessDeniedHttpException();
        }

        $newPublishedState = boolval($paramFetcher->get('isPublished'));
        $this->blogGateway->setPublished($blogId, $newPublishedState);

        return $this->handleView($this->view([], 200));
    }

    /**
     * Removes one blogpost from the database.
     *
     * @OA\Parameter(name="blogId", in="path", @OA\Schema(type="integer"), description="which post to delete")
     * @OA\Response(response="200", description="Success.")
     * @OA\Response(response="401", description="Not logged in.")
     * @OA\Response(response="403", description="Insufficient permissions to remove this blogpost.")
     * @OA\Response(response="404", description="Blogpost not found.")
     * @OA\Tag(name="blog")
     * @Rest\Delete("blog/{blogId}", requirements={"blogId" = "\d+"})
     */
    public function removeBlogpostAction(int $blogId): Response
    {
        $sessionId = $this->session->id();
        if (!$sessionId) {
            throw new UnauthorizedHttpException('', 'Not logged in.');
        }

        $author = $this->blogGateway->getAuthorOfPost($blogId);
        if ($author === false) {
            throw new NotFoundHttpException('Blogpost not found.');
        }
        if (!$this->blogPermissions->mayDelete($blogId)) {
            throw new AccessDeniedHttpException();
        }

        $this->blogGateway->del_blog_entry($blogId);

        return $this->handleView($this->view([], 200));
    }
}
