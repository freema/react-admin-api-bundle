import { Admin, Resource } from 'react-admin';
import { UserList, UserEdit, UserCreate, UserShow } from './users';


import simpleRestProvider from 'ra-data-simple-rest';
const dataProvider = simpleRestProvider('http://127.0.0.1:8080/api');


// Use bundle's custom data provider
//import { createDataProvider } from '../../../assets/dist/data-provider';
//const dataProvider = createDataProvider('http://127.0.0.1:8080/api');

function App() {
  return (
    <Admin dataProvider={dataProvider} title="React Admin API Test">
      <Resource 
        name="users" 
        list={UserList}
        edit={UserEdit}
        create={UserCreate}
        show={UserShow}
        recordRepresentation="name"
      />
    </Admin>
  );
}

export default App;