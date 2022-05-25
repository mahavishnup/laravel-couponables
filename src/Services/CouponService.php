<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;
use MichaelRubel\Couponables\Events\CouponExpired;
use MichaelRubel\Couponables\Events\CouponIsOverLimit;
use MichaelRubel\Couponables\Events\CouponIsOverQuantity;
use MichaelRubel\Couponables\Events\CouponRedeemed;
use MichaelRubel\Couponables\Events\CouponVerified;
use MichaelRubel\Couponables\Events\NotAllowedToRedeem;
use MichaelRubel\Couponables\Exceptions\CouponExpiredException;
use MichaelRubel\Couponables\Exceptions\InvalidCouponException;
use MichaelRubel\Couponables\Exceptions\NotAllowedToRedeemException;
use MichaelRubel\Couponables\Exceptions\OverLimitException;
use MichaelRubel\Couponables\Exceptions\OverQuantityException;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Contracts\CouponPivotContract;
use MichaelRubel\Couponables\Services\Contracts\CouponServiceContract;
use MichaelRubel\EnhancedContainer\Call;
use MichaelRubel\EnhancedContainer\Core\CallProxy;

class CouponService implements CouponServiceContract
{
    use Macroable;

    /**
     * @var CallProxy
     */
    public CallProxy $service;

    /**
     * @var CallProxy
     */
    public CallProxy $model;

    /**
     * @var CallProxy
     */
    public CallProxy $pivot;

    /**
     * @param CouponContract      $model
     * @param CouponPivotContract $pivot
     */
    public function __construct(CouponContract $model, CouponPivotContract $pivot)
    {
        $this->service = call($this);
        $this->model   = call($model);
        $this->pivot   = call($pivot);
    }

    /**
     * Get the coupon model by the code.
     *
     * @param string|null $code
     *
     * @return CouponContract|null
     */
    public function getCoupon(?string $code): ?CouponContract
    {
        return $this->model->firstWhere($this->model->getCodeColumn(), $code);
    }

    /**
     * Verify if coupon is valid otherwise throw an exception.
     *
     * @param string|null $code
     * @param Model       $redeemer
     *
     * @return CouponContract
     * @throws OverQuantityException
     * @throws OverLimitException
     * @throws NotAllowedToRedeemException
     * @throws CouponExpiredException
     * @throws InvalidCouponException
     */
    public function verifyCoupon(?string $code, Model $redeemer): CouponContract
    {
        $coupon = call($this->getCoupon($code) ?? throw new InvalidCouponException);

        if ($coupon->isExpired()) {
            event(new CouponExpired($coupon, $redeemer));

            throw new CouponExpiredException;
        }

        if ($coupon->isOverQuantity()) {
            event(new CouponIsOverQuantity($coupon, $redeemer));

            throw new OverQuantityException;
        }

        if ($coupon->isOverLimit($redeemer, $code)) {
            event(new CouponIsOverLimit($coupon, $redeemer));

            throw new OverLimitException;
        }

        if (! $coupon->isAllowedToRedeem($redeemer)) {
            event(new NotAllowedToRedeem($coupon, $redeemer));

            throw new NotAllowedToRedeemException;
        }

        event(new CouponVerified($coupon, $redeemer));

        return $coupon->getInternal(Call::INSTANCE);
    }

    /**
     * Apply the coupon.
     *
     * @param CouponContract $coupon
     * @param Model          $redeemer
     * @param Model|null     $redeemed
     *
     * @return CouponContract
     */
    public function applyCoupon(CouponContract $coupon, Model $redeemer, ?Model $redeemed): CouponContract
    {
        $redeemer->coupons()->attach($coupon, [
            $this->pivot->getRedeemedAtColumn()   => now(),
            $this->pivot->getRedeemedTypeColumn() => $redeemed?->getMorphClass(),
            $this->pivot->getRedeemedIdColumn()   => $redeemed?->id,
        ]);

        if (! is_null($coupon->{$this->model->getQuantityColumn()})) {
            $coupon->decrement($this->model->getQuantityColumn());
        }

        event(new CouponRedeemed($coupon, $redeemer));

        return $coupon;
    }
}
