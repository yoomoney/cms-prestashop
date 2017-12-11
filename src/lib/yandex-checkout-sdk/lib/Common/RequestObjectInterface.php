<?php

namespace YaMoney\Common;

interface RequestObjectInterface
{
    public function toJson();
    public function toArray();
}