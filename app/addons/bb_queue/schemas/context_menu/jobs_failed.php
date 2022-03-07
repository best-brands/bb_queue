<?php

use Tygh\ContextMenu\Items\GroupItem;

defined('BOOTSTRAP') or die('Access denied!');

return [
    'selectable_statuses' => [],
    'items'               => [
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'reschedule'      => [
                    'name'     => ['template' => 'reschedule_selected'],
                    'dispatch' => 'queue.m_jobs_failed_reschedule',
                    'position' => 10,
                ],
                'delete_selected' => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'queue.m_jobs_failed_delete',
                    'data'     => [
                        'action_class' => 'cm-confirm',
                    ],
                    'position' => 20,
                ],
            ],
            'position' => 40,
        ],
    ],
];
