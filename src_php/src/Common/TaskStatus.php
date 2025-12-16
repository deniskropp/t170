<?php

namespace MultiPersona\Common;

enum TaskStatus: string
{
    case Pending = 'Pending';
    case Ready = 'Ready';
    case InProgress = 'InProgress';
    case Completed = 'Completed';
    case Failed = 'Failed';
    case Blocked = 'Blocked';
}