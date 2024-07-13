<?php
declare(strict_types=1);

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Entities\Faculty;
use App\Enums\Gender;
use App\Exceptions\ValidationException;
use App\Services\FacultyService;
use Doctrine\ORM\EntityManager;
use Valitron\Validator;

class RegisterStudentRequestValidator implements RequestValidatorInterface
{
    public function __construct(private readonly FacultyService $facultyService)
    {
    }

    public function validate(array $data): array
    {
        $v = new Validator($data);

        // add rules
        $v->rule(
            'required',
            array_keys($data) // TODO: add the true fields
        );
        $v->rule('alpha', 'name');
        $v->rule('numeric', ['phone', 'ssn']);
        $v->rule('length', 'ssn', 14);
        $v->rule('lengthMin', 'phone', 10);
        $v->rule('date', 'birthdate');
        $v->rule('equals', 'password', 'confirmPassword');
        // TODO: add regex for password rules checking
        // $v->rule('in', 'userType', ['student', 'professor']);

        // gender
        $v->rule('in', 'gender', ['male', 'female']);
        $data['gender'] = ($data['gender'] == 'male')? Gender::Male : Gender::Female;

        // faculty from id to object
        $v->rule(function($field, $value, $params, $fields) use (&$data){
            $id = (int) $value;
            if(! $id) {
                return false;
            }

            $faculty = $this->facultyService->fetchById($id);

            if($faculty === null) {
                return false;
            }

            $data['faculty'] = $faculty;
            return true;
        }, 'faculty')->message('Faculty not found');

        // end of rules-------------------

        if(! $v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}