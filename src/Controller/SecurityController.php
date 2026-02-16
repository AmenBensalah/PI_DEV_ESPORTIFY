<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\SecurityAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private const FACE_ID_THRESHOLD = 0.47;

    #[Route('/', name: 'app_root')]
    public function root(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('fil_home');
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/login/face-id', name: 'app_login_face_id', methods: ['POST'])]
    public function loginWithFaceId(
        Request $request,
        UserRepository $userRepository,
        Security $security
    ): JsonResponse {
        try {
            $payload = json_decode((string) $request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['ok' => false, 'message' => 'Invalid Face ID payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($payload)) {
            return $this->json(['ok' => false, 'message' => 'Invalid Face ID payload.'], Response::HTTP_BAD_REQUEST);
        }

        $incoming = $this->normalizeFaceDescriptor($payload['descriptor'] ?? null);
        if ($incoming === null) {
            return $this->json(['ok' => false, 'message' => 'Invalid face descriptor.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $bestUser = null;
        $bestDistance = null;

        foreach ($userRepository->findUsersWithFaceDescriptor() as $candidate) {
            $stored = $this->normalizeFaceDescriptor($candidate->getFaceDescriptor());
            if ($stored === null) {
                continue;
            }

            $distance = $this->euclideanDistance($incoming, $stored);
            if ($bestDistance === null || $distance < $bestDistance) {
                $bestDistance = $distance;
                $bestUser = $candidate;
            }
        }

        if (!$bestUser instanceof User || $bestDistance === null || $bestDistance > self::FACE_ID_THRESHOLD) {
            return $this->json(['ok' => false, 'message' => 'Face ID not recognized.'], Response::HTTP_UNAUTHORIZED);
        }

        $loginResponse = $security->login($bestUser, SecurityAuthenticator::class, 'main');
        $redirect = $this->generateUrl('fil_home');
        if ($loginResponse instanceof RedirectResponse) {
            $redirect = $loginResponse->getTargetUrl();
        }

        return $this->json([
            'ok' => true,
            'redirect' => $redirect,
            'distance' => round($bestDistance, 4),
        ]);
    }

    /**
     * @param mixed $descriptor
     * @return list<float>|null
     */
    private function normalizeFaceDescriptor(mixed $descriptor): ?array
    {
        if (!is_array($descriptor) || count($descriptor) !== 128) {
            return null;
        }

        $normalized = [];
        foreach ($descriptor as $value) {
            if (!is_int($value) && !is_float($value)) {
                return null;
            }

            $floatValue = (float) $value;
            if (!is_finite($floatValue) || abs($floatValue) > 10.0) {
                return null;
            }

            $normalized[] = $floatValue;
        }

        return $normalized;
    }

    /**
     * @param list<float> $a
     * @param list<float> $b
     */
    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;

        for ($i = 0; $i < 128; $i++) {
            $delta = $a[$i] - $b[$i];
            $sum += $delta * $delta;
        }

        return sqrt($sum);
    }
}
