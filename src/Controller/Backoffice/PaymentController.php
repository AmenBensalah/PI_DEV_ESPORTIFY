<?php

namespace App\Controller\Backoffice;

use App\Entity\Payment;
use App\Repository\PaymentRepository;
use App\Service\RevenueForecastService;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/payment')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'admin_payment_index', methods: ['GET'])]
    public function index(
        PaymentRepository $paymentRepository,
        RevenueForecastService $revenueForecastService,
        Request $request
    ): Response
    {
        [
            'query' => $query,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
        ] = $this->resolveListFilters($request);

        $payments = $paymentRepository->searchAndSort($query, $status, $sort, $direction);

        if ($request->isXmlHttpRequest() || $request->query->getBoolean('ajax')) {
            return $this->render('backoffice/payment/_table.html.twig', [
                'payments' => $payments,
            ]);
        }

        $analytics = $revenueForecastService->buildPaymentForecastDashboard();

        return $this->render('backoffice/payment/index.html.twig', [
            'payments' => $payments,
            'analytics' => $analytics,
            'currentQuery' => $query,
            'currentStatus' => $status,
            'currentSort' => $sort,
            'currentDirection' => $direction,
        ]);
    }

    #[Route('/export/pdf', name: 'admin_payment_export_pdf', methods: ['GET'])]
    public function exportPdf(
        PaymentRepository $paymentRepository,
        Request $request,
        Pdf $pdf
    ): Response {
        [
            'query' => $query,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
        ] = $this->resolveListFilters($request);

        $payments = $paymentRepository->searchAndSort($query, $status, $sort, $direction);
        $generatedAt = new \DateTimeImmutable();

        $html = $this->renderView('backoffice/payment/export_pdf.html.twig', [
            'payments' => $payments,
            'currentQuery' => $query,
            'currentStatus' => $status,
            'currentSort' => $sort,
            'currentDirection' => $direction,
            'generatedAt' => $generatedAt,
        ]);

        try {
            $pdf->setBinary($this->resolveWkhtmltopdfBinary());
            $output = $pdf->getOutputFromHtml($html, [
                'encoding' => 'UTF-8',
                'page-size' => 'A4',
                'orientation' => 'Landscape',
                'margin-top' => 10,
                'margin-right' => 8,
                'margin-bottom' => 10,
                'margin-left' => 8,
                'footer-right' => '[page]/[toPage]',
                'footer-font-size' => 8,
                'enable-local-file-access' => true,
            ]);
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Export PDF indisponible: ' . $exception->getMessage());

            return $this->redirectToRoute('admin_payment_index', [
                'q' => $query,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ]);
        }

        $fileName = sprintf('paiements_%s.pdf', $generatedAt->format('Ymd_His'));

        return new Response($output, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
        ]);
    }

    #[Route('/{id}', name: 'admin_payment_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Payment $payment): Response
    {
        return $this->render('backoffice/payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }

    /**
     * @return array{query: string, status: string, sort: string, direction: string}
     */
    private function resolveListFilters(Request $request): array
    {
        return [
            'query' => trim((string) $request->query->get('q', '')),
            'status' => trim((string) $request->query->get('status', '')),
            'sort' => trim((string) $request->query->get('sort', 'id')),
            'direction' => strtoupper(trim((string) $request->query->get('direction', 'DESC'))) === 'ASC' ? 'ASC' : 'DESC',
        ];
    }

    private function resolveWkhtmltopdfBinary(): string
    {
        $envValue = $_SERVER['WKHTMLTOPDF_PATH'] ?? $_ENV['WKHTMLTOPDF_PATH'] ?? getenv('WKHTMLTOPDF_PATH') ?: '';
        $binary = trim((string) $envValue, " \t\n\r\0\x0B\"'");

        if ($binary !== '' && is_file($binary)) {
            $normalized = $this->normalizeWindowsBinaryPath($binary);
            if ($normalized !== '') {
                return $normalized;
            }

            return $binary;
        }

        if (\DIRECTORY_SEPARATOR === '\\') {
            $windowsCandidates = [
                'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
                'C:\\Program Files (x86)\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
            ];

            foreach ($windowsCandidates as $candidate) {
                if (is_file($candidate)) {
                    $normalized = $this->normalizeWindowsBinaryPath($candidate);
                    if ($normalized !== '') {
                        return $normalized;
                    }

                    return $candidate;
                }
            }
        }

        return $binary !== '' ? $binary : 'wkhtmltopdf';
    }

    private function normalizeWindowsBinaryPath(string $binary): string
    {
        if (\DIRECTORY_SEPARATOR !== '\\') {
            return $binary;
        }

        if (!str_contains($binary, ' ')) {
            return $binary;
        }

        $shortPathCandidates = [
            str_replace('C:\\Program Files\\', 'C:\\Progra~1\\', $binary),
            str_replace('C:\\Program Files (x86)\\', 'C:\\Progra~2\\', $binary),
        ];

        foreach ($shortPathCandidates as $shortPath) {
            if ($shortPath !== $binary && is_file($shortPath)) {
                return $shortPath;
            }
        }

        return $binary;
    }
}
