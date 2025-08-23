<?php

namespace Girgias\StubToDocbook\MetaData;

enum InitializerVariant
{
    case Literal;
    case Constant;
    /** Unused for now as not all class constant use the constant tag */
    case Enum;
    case Function;
    case BitMask;
    case Text;
}
