<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateRedistribucionConfigRequest;
use App\Http\Requests\UpdateRedistribucionConfigRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\RedistribucionConfigRepository;
use Illuminate\Http\Request;
use Flash;

class RedistribucionConfigController extends AppBaseController
{
    /** @var RedistribucionConfigRepository $redistribucionConfigRepository*/
    private $redistribucionConfigRepository;

    public function __construct(RedistribucionConfigRepository $redistribucionConfigRepo)
    {
        $this->redistribucionConfigRepository = $redistribucionConfigRepo;
    }

    /**
     * Display a listing of the RedistribucionConfig.
     */
    public function index(Request $request)
    {
        $redistribucionConfigs = $this->redistribucionConfigRepository->paginate(10);

        return view('redistribucion_configs.index')
            ->with('redistribucionConfigs', $redistribucionConfigs);
    }

    /**
     * Show the form for creating a new RedistribucionConfig.
     */
    public function create()
    {
        return view('redistribucion_configs.create');
    }

    /**
     * Store a newly created RedistribucionConfig in storage.
     */
    public function store(CreateRedistribucionConfigRequest $request)
    {
        $input = $request->all();

        $redistribucionConfig = $this->redistribucionConfigRepository->create($input);

        Flash::success('Redistribucion Config saved successfully.');

        return redirect(route('redistribucionConfigs.index'));
    }

    /**
     * Display the specified RedistribucionConfig.
     */
    public function show($id)
    {
        $redistribucionConfig = $this->redistribucionConfigRepository->find($id);

        if (empty($redistribucionConfig)) {
            Flash::error('Redistribucion Config not found');

            return redirect(route('redistribucionConfigs.index'));
        }

        return view('redistribucion_configs.show')->with('redistribucionConfig', $redistribucionConfig);
    }

    /**
     * Show the form for editing the specified RedistribucionConfig.
     */
    public function edit($id)
    {
        $redistribucionConfig = $this->redistribucionConfigRepository->find($id);

        if (empty($redistribucionConfig)) {
            Flash::error('Redistribucion Config not found');

            return redirect(route('redistribucionConfigs.index'));
        }

        return view('redistribucion_configs.edit')->with('redistribucionConfig', $redistribucionConfig);
    }

    /**
     * Update the specified RedistribucionConfig in storage.
     */
    public function update($id, UpdateRedistribucionConfigRequest $request)
    {
        $redistribucionConfig = $this->redistribucionConfigRepository->find($id);

        if (empty($redistribucionConfig)) {
            Flash::error('Redistribucion Config not found');

            return redirect(route('redistribucionConfigs.index'));
        }

        $redistribucionConfig = $this->redistribucionConfigRepository->update($request->all(), $id);

        Flash::success('Redistribucion Config updated successfully.');

        return redirect(route('redistribucionConfigs.index'));
    }

    /**
     * Remove the specified RedistribucionConfig from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $redistribucionConfig = $this->redistribucionConfigRepository->find($id);

        if (empty($redistribucionConfig)) {
            Flash::error('Redistribucion Config not found');

            return redirect(route('redistribucionConfigs.index'));
        }

        $this->redistribucionConfigRepository->delete($id);

        Flash::success('Redistribucion Config deleted successfully.');

        return redirect(route('redistribucionConfigs.index'));
    }
}
