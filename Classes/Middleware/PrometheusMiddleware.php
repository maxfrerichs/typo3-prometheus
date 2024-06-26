<?php
namespace MFR\Typo3Prometheus\Middleware;

use MFR\Typo3Prometheus\Service\PrometheusService;
use Prometheus\RenderTextFormat;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;


class PrometheusMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly PrometheusService $prometheusService
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $requestPort = $request->getServerParams()['SERVER_PORT'];
        $metricsPort = $this->extensionConfiguration->get('typo3_prometheus', 'metricsPort') ?? 9090;
        $metricsPath = $this->extensionConfiguration->get('typo3_prometheus', 'metricsPath');
        if (($request->getRequestTarget() == $metricsPath)) {
            // block access from HTTP ports.
            if ($requestPort != $metricsPort || $requestPort == 80 || $requestPort == 443) {
                return $this->responseFactory->createResponse(403);
            }
            $result = $this->prometheusService->renderMetrics();
            echo $result;
            return $this->responseFactory->createResponse(200)->withHeader('Content-Type', RenderTextFormat::MIME_TYPE);
        } else {
            return $handler->handle($request);
        }
    }
}
