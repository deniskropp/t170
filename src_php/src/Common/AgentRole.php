<?php

namespace MultiPersona\Common;

enum AgentRole: string
{
    case Orchestrator = 'Orchestrator';
    case RoleDefiner = 'RoleDefiner';
    case PromptEngineer = 'PromptEngineer';
    case ProtocolEstablisher = 'ProtocolEstablisher';
    case SystemMonitor = 'SystemMonitor';
    case MetaCommunicator = 'MetaCommunicator';
    case FizzLaMetta = 'FizzLaMetta';
    case KickLaMetta = 'KickLaMetta';
    case Dima = 'Dima';
    case AR00L = 'AR-00L';
    case QllickBuzzFizz = 'QllickBuzz & QllickFizz';
    case WePlan = 'WePlan';
    case Codein = 'Codein';
}