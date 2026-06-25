<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Governance;

/**
 * Feature di governance governate dalla primitiva FeatureScope.
 * Vedi laravel-iam-docs/14-governance-and-iga.md §1.
 */
enum FeatureKey: string
{
    case AccessReview = 'access_review';
    case AccessRequest = 'access_request';
    case Pim = 'pim';
    case SoD = 'sod';
    case LeastPrivilege = 'least_privilege';
    case AnomalyDetection = 'anomaly_detection';
}
