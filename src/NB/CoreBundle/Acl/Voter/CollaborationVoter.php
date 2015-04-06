<?php
namespace NB\CoreBundle\Acl\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class CollaborationVoter implements VoterInterface
{
	private $supportedAttributes = [
		'VIEW',
		'INDEX',
		'EDIT',
		'DELETE'
	];
	private $entityConfigProvider, $logger, $doctrineHelprer;

	public function __construct($entityConfigProvider, $doctrineHelprer, $logger){
		$this->entityConfigProvider = $entityConfigProvider;
		$this->doctrineHelprer = $doctrineHelprer;
		$this->logger = $logger;
	}

	public function supportsAttribute($attribute){
		return $attribute ? in_array($attribute, $this->supportedAttributes) : false;
	}

	public function supportsClass($class){
		if(!$class) return false;
		return $this->entityConfigProvider->hasConfig($class);
	}

	public function Vote(TokenInterface $token, $object, array $attributes){
		//not dealing with descriptors
		//or object identities
		if(!is_object($object)){
			$this->logger->notice(gettype($object));
			$this->logger->notice(join(',',$attributes));
			return VoterInterface::ACCESS_ABSTAIN;
		}

		if($object instanceof ObjectIdentityInterface){
			$this->logger->notice('ObjectIdentity is ' . (string) $object);
			  //$this->logger->notice('ObjectIdentity is ' . (string) $object->getType());
				return VoterInterface::ACCESS_ABSTAIN;

			// $config = $this->entityConfigProvider->getConfig($object->getType());
			// if(!$config->has('collaboration'))
			// 	return VoterInterface::ACCESS_ABSTAIN;

			// $this->logger->notice($this->doctrineHelprer->getSingleEntityIdentifier($object));
			// return VoterInterface::ACCESS_ABSTAIN;
		}
		else
			return VoterInterface::ACCESS_ABSTAIN;
		$className = ClassUtils::getClass($object);
		$this->logger->notice('Class is ' . $className);
		
		if(!$this->supportsClass($className))
			return VoterInterface::ACCESS_ABSTAIN;

		//$this->logger->notice('Class is supported ' . $className);
		foreach ($attributes as $attribute) {
			
			if(!$this->supportsAttribute($attribute))
				continue;

			$config = $this->entityConfigProvider->getConfig($className);
			if(!$config->has('collaboration'))
				return VoterInterface::ACCESS_ABSTAIN;

			//$this->logger->notice('Theres a collaboration config');

			if($config->get('collaboration') && method_exists($object, 'getUsers')){
				$this->logger->notice('Checking against ' . $attributes[0]);
				$usersCollection = $object->getUsers();
				if($usersCollection->contains($token->getUser())){
					$this->logger->notice('GRANTED');
					return VoterInterface::ACCESS_GRANTED;
				}
				return VoterInterface::ACCESS_ABSTAIN;
			}
			else{
				return VoterInterface::ACCESS_ABSTAIN;
			}
		}
		return VoterInterface::ACCESS_ABSTAIN;
	}
}
?>